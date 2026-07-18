<?php

namespace Database\Seeders;

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

    $admin = User::updateOrCreate(
        ['email' => 'admin@demo.localhost'],
        [
            'name' => 'Admin',
            'password' => Hash::make('password123'),
        ]
    );

    $admin->syncRoles('Admin Tenant');

    $this->command->info('✅ Admin created');


    $staff = User::updateOrCreate(
        ['email' => 'staff@demo.localhost'],
        [
            'name' => 'Staff Member',
            'password' => Hash::make('password123'),
        ]
    );

    $staff->syncRoles('Staff');

    $this->command->info('✅ Staff created');


    $customer = User::updateOrCreate(
        ['email' => 'customer@demo.localhost'],
        [
            'name' => 'Customer Demo',
            'password' => Hash::make('password123'),
        ]
    );

    $customer->syncRoles('Customer');

    $this->command->info('✅ Customer created');

    $this->command->info('✨ Tenant users seeded successfully!');
}
}
