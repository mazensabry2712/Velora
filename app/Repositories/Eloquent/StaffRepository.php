<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Models\StaffWorkingHours;
use App\Models\UsageLog;
use App\Models\User;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffRepository implements StaffRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findWithRelations(int $id, array $relations = []): ?User
    {
        return User::with($relations)->findOrFail($id);
    }

    public function all(): Collection
    {
        return User::role('Staff')
            ->with(['services', 'activeSchedules'])
            ->get();
    }

    public function create(array $userData, array $services = [], array $schedule = []): User
    {
        $staffRole = Role::where('name', 'Staff')->firstOrFail();

        return DB::transaction(function () use ($userData, $services, $schedule, $staffRole) {
            $defaultPassword = explode('@', $userData['email'])[0] . '123';

            $user = User::create([
                'name'           => $userData['name'],
                'email'          => $userData['email'],
                'phone'          => $userData['phone'] ?? null,
                'password'       => Hash::make($defaultPassword),
                'specialization' => $userData['specialization'] ?? null,
            ]);

            $user->assignRole($staffRole);

            $nameParts = preg_split('/\s+/', trim($userData['name']), 2);
            $staff = Staff::create([
                'user_id'          => $user->id,
                'first_name'       => $nameParts[0] ?? '',
                'last_name'        => $nameParts[1] ?? '',
                'email'            => $user->email,
                'phone'            => $user->phone,
                'title'            => !empty($userData['specialization']) ? ['en' => $userData['specialization']] : null,
                'is_active'        => true,
                'accepts_bookings' => true,
                'sort_order'       => 0,
            ]);

            if (!empty($services)) {
                $this->syncServices($user->id, $staff->id, $services);
            }

            if (!empty($schedule)) {
                foreach ($schedule as $day => $hours) {
                    if (!is_array($hours)) {
                        continue;
                    }

                    $dayOfWeek = isset($hours['day_of_week']) ? (int) $hours['day_of_week'] : (int) $day;
                    $isWorking = filter_var($hours['is_working'] ?? $hours['working'] ?? true, FILTER_VALIDATE_BOOLEAN);

                    StaffWorkingHours::updateOrCreate(
                        ['staff_id' => $staff->id, 'day_of_week' => $dayOfWeek],
                        [
                            'start_time' => $hours['start_time'] ?? '09:00',
                            'end_time'   => $hours['end_time'] ?? '17:00',
                            'is_working' => $isWorking,
                        ]
                    );
                }

                $this->syncSchedule($user->id, $schedule);
            }

            try {
                UsageLog::log('user_created', [
                    'user_id'   => $user->id,
                    'user_type' => 'staff',
                    'name'      => $user->name,
                    'email'     => $user->email,
                ]);
            } catch (\Throwable) {
                // Logging failure must never roll back the main transaction.
            }

            return $user->load(['services', 'schedules']);
        });
    }

    public function update(User $staff, array $userData, array $services = [], array $schedule = []): bool
    {
        return DB::transaction(function () use ($staff, $userData, $services, $schedule) {
            $staff->update([
                'name'           => $userData['name'],
                'email'          => $userData['email'],
                'phone'          => $userData['phone'] ?? null,
                'specialization' => $userData['specialization'] ?? $staff->specialization,
            ]);

            if (isset($userData['password'])) {
                $staff->update(['password' => Hash::make($userData['password'])]);
            }

            $staffRecord = Staff::where('user_id', $staff->id)->first();

            if (!$staffRecord) {
                $parts = preg_split('/\s+/', trim($staff->name), 2);
                $staffRecord = Staff::create([
                    'user_id'          => $staff->id,
                    'first_name'       => $parts[0] ?? '',
                    'last_name'        => $parts[1] ?? '',
                    'email'            => $staff->email,
                    'phone'            => $staff->phone,
                    'title'            => !empty($staff->specialization) ? ['en' => $staff->specialization] : null,
                    'is_active'        => true,
                    'accepts_bookings' => true,
                    'sort_order'       => 0,
                ]);
            } else {
                $parts = preg_split('/\s+/', trim($staff->name), 2);
                $staffRecord->update([
                    'first_name' => $parts[0] ?? '',
                    'last_name'  => $parts[1] ?? '',
                    'email'      => $staff->email,
                    'phone'      => $staff->phone,
                    'title'      => !empty($staff->specialization) ? ['en' => $staff->specialization] : $staffRecord->title,
                ]);
            }

            // Keep both sides of the staff-service pivot consistent.
            // User::services() reads the user_id side while Staff::services() reads staff_id.
            $this->syncServices($staff->id, $staffRecord->id, $services);
            $this->syncSchedule($staff->id, $schedule);

            return true;
        });
    }

    public function delete(User $staff): bool
    {
        return DB::transaction(function () use ($staff) {
            StaffSchedule::where('user_id', $staff->id)->delete();

            $staffRecord = Staff::where('user_id', $staff->id)->first();
            if ($staffRecord) {
                DB::table('staff_services')
                    ->where('staff_id', $staffRecord->id)
                    ->orWhere('user_id', $staff->id)
                    ->delete();
                $staffRecord->delete();
            } else {
                DB::table('staff_services')->where('user_id', $staff->id)->delete();
            }

            try {
                UsageLog::log('user_deleted', [
                    'user_id' => $staff->id,
                    'name'    => $staff->name,
                    'email'   => $staff->email,
                ]);
            } catch (\Throwable) {
                // Logging failure must never roll back the main transaction.
            }

            return (bool) $staff->delete();
        });
    }

    public function getBySpecialization(string $specialization): Collection
    {
        return User::where('specialization', $specialization)
            ->role('Staff')
            ->get(['id', 'name', 'specialization']);
    }

    public function getByService(int $serviceId): Collection
    {
        return User::whereHas('services', fn ($q) => $q->where('services.id', $serviceId))
            ->role('Staff')
            ->with(['activeSchedules'])
            ->get(['id', 'name']);
    }

    public function getSchedule(int $staffId): Collection
    {
        return StaffSchedule::where('user_id', $staffId)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->get();
    }

    private function syncServices(int $userId, int $staffId, array $serviceIds): void
    {
        DB::table('staff_services')
            ->where('user_id', $userId)
            ->orWhere('staff_id', $staffId)
            ->delete();

        foreach (array_unique(array_map('intval', $serviceIds)) as $serviceId) {
            DB::table('staff_services')->insert([
                'user_id'    => $userId,
                'staff_id'   => $staffId,
                'service_id' => $serviceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function syncSchedule(int $staffId, array $schedule): void
    {
        StaffSchedule::where('user_id', $staffId)->delete();

        foreach ($schedule as $row) {
            StaffSchedule::create([
                'user_id'     => $staffId,
                'day_of_week' => $row['day_of_week'],
                'start_time'  => $row['start_time'],
                'end_time'    => $row['end_time'],
                'is_active'   => $row['is_active'] ?? true,
            ]);
        }
    }
}
