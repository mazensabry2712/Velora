<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Make sure roles exist
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'Super Admin'],
            ['guard_name' => 'web']
        );

        // Create Super Admin user (no tenant_id = central user)
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@booking-saas.test'],
            [
                'name' => 'Super Admin',
                'tenant_id' => null, // Central user, not tied to any tenant
                'role_id' => $superAdminRole->id,
                'password' => Hash::make('password'),
            ]
        );

        // Assign Super Admin role
        $superAdmin->assignRole('Super Admin');

        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: superadmin@booking-saas.test');
        $this->command->info('Password: password');
    }
}
