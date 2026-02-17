<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for Super Admin (Central)
        $superAdminPermissions = [
            'manage-tenants',
            'create-tenant',
            'update-tenant',
            'delete-tenant',
            'view-tenant-statistics',
            'activate-tenant',
            'deactivate-tenant',
        ];

        foreach ($superAdminPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create permissions for Admin Tenant
        $adminTenantPermissions = [
            'manage-users',
            'manage-staff',
            'manage-appointments',
            'manage-queues',
            'manage-invoices',
            'manage-notifications',
            'manage-settings',
            'view-reports',
        ];

        foreach ($adminTenantPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create permissions for Staff
        $staffPermissions = [
            'view-appointments',
            'create-appointment',
            'update-appointment',
            'view-queue',
            'update-queue',
            'view-customers',
        ];

        foreach ($staffPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create permissions for Customer
        $customerPermissions = [
            'view-own-appointments',
            'create-own-appointment',
            'cancel-own-appointment',
            'view-own-invoices',
            'view-own-queue',
        ];

        foreach ($customerPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin Role (Central)
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->syncPermissions($superAdminPermissions);

        // Admin Tenant Role
        $adminTenantRole = Role::firstOrCreate(['name' => 'Admin Tenant']);
        $adminTenantRole->syncPermissions($adminTenantPermissions);

        // Staff Role
        $staffRole = Role::firstOrCreate(['name' => 'Staff']);
        $staffRole->syncPermissions($staffPermissions);

        // Customer Role
        $customerRole = Role::firstOrCreate(['name' => 'Customer']);
        $customerRole->syncPermissions($customerPermissions);

        $this->command->info('Roles and permissions created successfully!');
    }
}
