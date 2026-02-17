<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'permissions' => json_encode([
                    'manage_appointments',
                    'manage_staff',
                    'manage_queue',
                    'view_reports',
                    'manage_settings',
                ]),
            ],
            [
                'name' => 'Staff',
                'permissions' => json_encode([
                    'view_appointments',
                    'manage_queue',
                ]),
            ],
            [
                'name' => 'Customer',
                'permissions' => json_encode([
                    'book_appointment',
                    'view_own_appointments',
                ]),
            ],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::create($role);
        }
    }
}
