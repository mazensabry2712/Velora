<?php

namespace App\Repositories\Eloquent;

use Spatie\Permission\Models\Role;
use App\Models\StaffSchedule;
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

            if (!empty($services)) {
                $user->services()->sync($services);
            }

            if (!empty($schedule)) {
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

            $staff->services()->sync($services);
            $this->syncSchedule($staff->id, $schedule);

            return true;
        });
    }

    public function delete(User $staff): bool
    {
        return DB::transaction(function () use ($staff) {
            StaffSchedule::where('user_id', $staff->id)->delete();
            $staff->services()->detach();

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
        return User::whereHas('services', fn($q) => $q->where('services.id', $serviceId))
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

    // ── Private helpers ──────────────────────────────────────────────────

    // private function syncSchedule(int $staffId, array $schedule): void
    // {
    //     StaffSchedule::where('user_id', $staffId)->delete();

    //     foreach ($schedule as $row) {
    //         StaffSchedule::create([
    //             'user_id'     => $staffId,
    //             'day_of_week' => $row['day_of_week'],
    //             'start_time'  => $row['start_time'],
    //             'end_time'    => $row['end_time'],
    //             'is_active'   => $row['is_active'] ?? true,
    //         ]);
    //     }
    // }


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
