<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a demo tenant with custom data
        $tenant = Tenant::create([
            'id' => Str::uuid()->toString(),
            'name' => 'Demo Clinic',
            'active' => true,
        ]);

        // Create domain
        $tenant->domains()->create([
            'domain' => 'demo.localhost',
        ]);

        // Add booking-saas.test domain
        $tenant->domains()->create([
            'domain' => 'booking-saas.test',
        ]);

        $this->command->info('Demo tenant created: demo.localhost / booking-saas.test');
        $this->command->info('Tenant ID: ' . $tenant->id);

        // Run tenant database migrations and seeders
        $tenant->run(function () use ($tenant) {
            // Run migrations inside tenant context
            \Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            // Seed roles
            $roles = [
                'Admin Tenant' => ['manage_appointments', 'manage_staff', 'manage_queue', 'view_reports', 'manage_settings', 'manage_assistants'],
                'Staff' => ['view_appointments', 'manage_queue'],
                'Assistant' => ['manage_appointments', 'manage_queue'],
                'Customer' => ['book_appointment', 'view_own_appointments'],
            ];

            foreach ($roles as $name => $permissions) {
                \App\Models\Role::create([
                    'name' => $name,
                    'permissions' => json_encode($permissions),
                ]);
            }

            // Create admin user
            $adminRole = \App\Models\Role::where('name', 'Admin Tenant')->first();
            \App\Models\User::create([
                'role_id' => $adminRole->id,
                'name' => 'Admin User',
                'email' => 'admin@demo.localhost',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
            ]);

            // Create staff user
            $staffRole = \App\Models\Role::where('name', 'Staff')->first();
            \App\Models\User::create([
                'role_id' => $staffRole->id,
                'name' => 'Staff User',
                'email' => 'staff@demo.localhost',
                'password' => bcrypt('password123'),
                'email_verified_at' => now(),
                'specialization' => 'General',
                'specialization_ar' => 'عام',
            ]);

            // Seed working days
            $days = [
                ['day_of_week' => 0, 'day_name' => 'Sunday', 'day_name_ar' => 'الأحد', 'is_active' => true],
                ['day_of_week' => 1, 'day_name' => 'Monday', 'day_name_ar' => 'الإثنين', 'is_active' => true],
                ['day_of_week' => 2, 'day_name' => 'Tuesday', 'day_name_ar' => 'الثلاثاء', 'is_active' => true],
                ['day_of_week' => 3, 'day_name' => 'Wednesday', 'day_name_ar' => 'الأربعاء', 'is_active' => true],
                ['day_of_week' => 4, 'day_name' => 'Thursday', 'day_name_ar' => 'الخميس', 'is_active' => true],
                ['day_of_week' => 5, 'day_name' => 'Friday', 'day_name_ar' => 'الجمعة', 'is_active' => false],
                ['day_of_week' => 6, 'day_name' => 'Saturday', 'day_name_ar' => 'السبت', 'is_active' => false],
            ];
            foreach ($days as $day) {
                \App\Models\WorkingDay::create($day);
            }

            // Seed time slots (9 AM to 5 PM, 30 min intervals)
            $startHour = 9;
            $endHour = 17;
            for ($h = $startHour; $h < $endHour; $h++) {
                \App\Models\TimeSlot::create([
                    'start_time' => sprintf('%02d:00', $h),
                    'end_time' => sprintf('%02d:30', $h),
                    'is_active' => true,
                ]);
                \App\Models\TimeSlot::create([
                    'start_time' => sprintf('%02d:30', $h),
                    'end_time' => sprintf('%02d:00', $h + 1),
                    'is_active' => true,
                ]);
            }

            // Seed a demo service
            \App\Models\Service::create([
                'name' => 'General Consultation',
                'name_ar' => 'استشارة عامة',
                'description' => 'General consultation service',
                'duration' => 30,
                'price' => 100.00,
                'is_active' => true,
            ]);

            // Seed default settings
            \App\Models\Setting::create([
                'tenant_id' => $tenant->id,
                'business_name' => 'Demo Clinic',
                'business_name_ar' => 'عيادة تجريبية',
                'language' => 'ar',
            ]);
        });

        $this->command->info('Demo users created (admin@demo.localhost / staff@demo.localhost)');
        $this->command->info('Password: password123');
    }
}

