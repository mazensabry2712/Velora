<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin role if not exists (Spatie Permission)
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        // Create Super Admin user (Central - No tenant)
        $user = User::firstOrCreate(
            ['email' => 'superadmin@bookingsaas.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('SuperAdmin@123'),
                'email_verified_at' => now(),
            ]
        );

        if (!$user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
        }

        $this->command->info('✅ Super Admin created successfully!');
        $this->command->info('📧 Email: superadmin@bookingsaas.com');
        $this->command->info('🔑 Password: SuperAdmin@123');
        $this->command->info('🔗 URL: http://velora.test/super-admin/login');
    }
}
