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
    /** @param array<string, mixed> $staffData */
    public function create(array $staffData, array $services = [], array $schedule = []): Staff
    {
        $staffRole = Role::where('name', 'Staff')->firstOrFail();

        $defaultPassword = explode('@', $staffData['email'])[0] . '123';
        $user = User::create([
            'name' => $staffData['name'],
            'email' => $staffData['email'],
            'phone' => $staffData['phone'] ?? null,
            'password' => Hash::make($defaultPassword),
            'specialization' => $staffData['specialization'] ?? null,
        ]);

        $user->assignRole($staffRole);

        $parts = preg_split('/\s+/', trim($staffData['name']), 2);
        $staff = Staff::create([
            'user_id' => $user->id,
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'email' => $user->email,
            'phone' => $user->phone,
            'title' => !empty($staffData['specialization']) ? ['en' => $staffData['specialization']] : null,
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

        return $staff->load(['user', 'services', 'workingHours']);
    }

    /** @param array<string, mixed> $staffData */
    public function update(Staff $staff, array $staffData, array $services = [], array $schedule = []): bool
    {
        $user = $staff->user;
        if (!$user) {
            return false;
        }

        $user->update([
            'name' => $staffData['name'],
            'email' => $staffData['email'],
            'phone' => $staffData['phone'] ?? null,
            'specialization' => $staffData['specialization'] ?? $user->specialization,
        ]);

        if (isset($staffData['password'])) {
            $user->update(['password' => Hash::make($staffData['password'])]);
        }

        $parts = preg_split('/\s+/', trim($user->name), 2);
        $staff->update([
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'email' => $user->email,
            'phone' => $user->phone,
            'title' => !empty($user->specialization) ? ['en' => $user->specialization] : $staff->title,
        ]);

        $this->syncServices($staff->id, $services);
        $this->syncScheduleData($staff->id, $schedule, true);

        return true;
    }

    public function delete(Staff $staff): bool
    {
        $user = $staff->user;

        StaffWorkingHours::where('staff_id', $staff->id)->delete();
        DB::table('staff_services')->where('staff_id', $staff->id)->delete();
        $staff->delete();

        try {
            UsageLog::log('user_deleted', [
                'user_id' => $user?->id,
                'name' => $user?->name,
                'email' => $user?->email,
            ]);
        } catch (\Throwable) {
        }

        return $user ? (bool) $user->delete() : true;
    }

    private function syncServices(int $staffId, array $serviceIds): void
    {
        DB::table('staff_services')->where('staff_id', $staffId)->delete();

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
