<?php

declare(strict_types=1);

namespace App\Infrastructure\Staff;

use App\Domain\Staff\Contracts\StaffWriter;
use App\Models\Staff;
use App\Models\StaffWorkingHours;
use App\Models\UsageLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

final class EloquentStaffWriter implements StaffWriter
{
    /** @param array<string, mixed> $userData */
    public function create(array $userData, array $services = [], array $schedule = []): User
    {
        $staffRole = Role::where('name', 'Staff')->firstOrFail();

        $defaultPassword = explode('@', $userData['email'])[0] . '123';
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'phone' => $userData['phone'] ?? null,
            'password' => Hash::make($defaultPassword),
            'specialization' => $userData['specialization'] ?? null,
        ]);

        $user->assignRole($staffRole);

        $parts = preg_split('/\s+/', trim($userData['name']), 2);
        $staff = Staff::create([
            'user_id' => $user->id,
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'email' => $user->email,
            'phone' => $user->phone,
            'title' => !empty($userData['specialization']) ? ['en' => $userData['specialization']] : null,
            'is_active' => true,
            'accepts_bookings' => true,
            'sort_order' => 0,
        ]);

        $this->syncServices($staff->id, $services);
        $this->syncScheduleData($staff->id, $schedule);

        try {
            UsageLog::log('user_created', [
                'user_id' => $user->id,
                'user_type' => 'staff',
                'name' => $user->name,
                'email' => $user->email,
            ]);
        } catch (\Throwable) {
        }

        return $user->load(['staffProfile.services', 'schedules']);
    }

    /** @param array<string, mixed> $userData */
    public function update(User $staff, array $userData, array $services = [], array $schedule = []): bool
    {
        $staff->update([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'phone' => $userData['phone'] ?? null,
            'specialization' => $userData['specialization'] ?? $staff->specialization,
        ]);

        if (isset($userData['password'])) {
            $staff->update(['password' => Hash::make($userData['password'])]);
        }

        $staffRecord = Staff::where('user_id', $staff->id)->first();
        $parts = preg_split('/\s+/', trim($staff->name), 2);

        if (!$staffRecord) {
            $staffRecord = Staff::create([
                'user_id' => $staff->id,
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? '',
                'email' => $staff->email,
                'phone' => $staff->phone,
                'title' => !empty($staff->specialization) ? ['en' => $staff->specialization] : null,
                'is_active' => true,
                'accepts_bookings' => true,
                'sort_order' => 0,
            ]);
        } else {
            $staffRecord->update([
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? '',
                'email' => $staff->email,
                'phone' => $staff->phone,
                'title' => !empty($staff->specialization) ? ['en' => $staff->specialization] : $staffRecord->title,
            ]);
        }

        $this->syncServices($staffRecord->id, $services);
        $this->syncScheduleData($staffRecord->id, $schedule, true);

        return true;
    }

    public function delete(User $staff): bool
    {
        $staffRecord = Staff::where('user_id', $staff->id)->first();
        if ($staffRecord) {
            StaffWorkingHours::where('staff_id', $staffRecord->id)->delete();
            DB::table('staff_services')->where('staff_id', $staffRecord->id)->delete();
            $staffRecord->delete();
        }

        try {
            UsageLog::log('user_deleted', [
                'user_id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
            ]);
        } catch (\Throwable) {
        }

        return (bool) $staff->delete();
    }

    private function syncServices(int $staffId, array $serviceIds): void
    {
        DB::table('staff_services')
            ->where('staff_id', $staffId)
            ->delete();

        foreach (array_unique(array_map('intval', $serviceIds)) as $serviceId) {
            DB::table('staff_services')->insert([
                'staff_id' => $staffId,
                'service_id' => $serviceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function syncScheduleData(int $staffId, array $schedule, bool $replace = false): void
    {
        if ($replace && $schedule === []) {
            StaffWorkingHours::where('staff_id', $staffId)->delete();
            return;
        }

        if ($schedule === []) {
            return;
        }

        foreach ($schedule as $day => $hours) {
            if (!is_array($hours)) {
                continue;
            }

            $dayOfWeek = isset($hours['day_of_week']) ? (int) $hours['day_of_week'] : (int) $day;
            $isWorking = filter_var(
                $hours['is_working'] ?? $hours['working'] ?? $hours['is_active'] ?? true,
                FILTER_VALIDATE_BOOLEAN
            );

            StaffWorkingHours::updateOrCreate(
                ['staff_id' => $staffId, 'day_of_week' => $dayOfWeek],
                [
                    'start_time' => $hours['start_time'] ?? null,
                    'end_time' => $hours['end_time'] ?? null,
                    'is_working' => $isWorking,
                ]
            );
        }
    }
}
