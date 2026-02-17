<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantUsersSeeder extends Seeder
{
    /**
     * Run the tenant users seeder.
     */
    public function run(): void
    {
        $this->command->info('🔄 Creating tenant users...');

        // Get or create roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin Tenant'],
            ['permissions' => ['all']]
        );

        $staffRole = Role::firstOrCreate(
            ['name' => 'Staff'],
            ['permissions' => ['manage_queue', 'manage_appointments', 'view_reports']]
        );

        $customerRole = Role::firstOrCreate(
            ['name' => 'Customer'],
            ['permissions' => ['book_appointment', 'view_own_queue']]
        );

        // Create Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@demo.localhost'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id,
            ]
        );
        $this->command->info('✅ Admin created: admin@demo.localhost / password123');

        // Create Staff User
        $staff = User::updateOrCreate(
            ['email' => 'staff@demo.localhost'],
            [
                'name' => 'Staff Member',
                'password' => Hash::make('password123'),
                'role_id' => $staffRole->id,
            ]
        );
        $this->command->info('✅ Staff created: staff@demo.localhost / password123');

        // Create Sample Customer
        $customer = User::updateOrCreate(
            ['email' => 'customer@demo.localhost'],
            [
                'name' => 'Customer Demo',
                'password' => Hash::make('password123'),
                'role_id' => $customerRole->id,
            ]
        );
        $this->command->info('✅ Customer created: customer@demo.localhost / password123');

        $this->command->info('✨ Tenant users seeded successfully!');
    }
}
