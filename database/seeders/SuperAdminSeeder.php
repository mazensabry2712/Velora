<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin role if not exists
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'Super Admin'],
            ['permissions' => []]
        );

        // Create Super Admin user (Central - No tenant)
        // Note: We use DB directly to avoid model fillable issues
        $existingUser = User::where('email', 'superadmin@bookingsaas.com')->first();

        if (!$existingUser) {
            DB::table('users')->insert([
                'name' => 'Super Admin',
                'email' => 'superadmin@bookingsaas.com',
                'password' => Hash::make('SuperAdmin@123'),
                'role_id' => $superAdminRole->id,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✅ Super Admin created successfully!');
        $this->command->info('📧 Email: superadmin@bookingsaas.com');
        $this->command->info('🔑 Password: SuperAdmin@123');
        $this->command->info('🔗 URL: http://booking-saas.test/super-admin/login');
    }
}
