<?php

namespace Tests;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Spatie\Permission\Models\Role;

/**
 * Base test case for all SuperAdmin-scoped tests.
 */
abstract class SuperAdminTestCase extends TestCase
{
    protected User $superAdmin;
    protected Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $centralConn = (string) config('tenancy.database.central_connection', 'mysql');

        Artisan::call('migrate:fresh', [
            '--database' => $centralConn,
            '--force' => true,
        ]);

        $schema = Schema::connection($centralConn);

        if (! $schema->hasTable('upgrade_requests')) {
            $schema->create('upgrade_requests', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id');
                $table->unsignedBigInteger('current_plan_id')->nullable();
                $table->unsignedBigInteger('requested_plan_id')->nullable();
                $table->string('status')->default('pending');
                $table->string('requested_by_name')->nullable();
                $table->string('requested_by_email')->nullable();
                $table->text('message')->nullable();
                $table->text('admin_notes')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->unsignedBigInteger('processed_by')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('roles')) {
            $schema->create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('users')) {
            $schema->create('users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
                $table->string('tenant_id')->nullable();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('settings')) {
            $schema->create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id')->nullable();
                $table->string('business_name')->nullable();
                $table->timestamps();
            });
        }

        $this->superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin@velora.test',
            'password' => Hash::make('password'),
            'role_id' => $this->superAdminRole->id,
        ]);

        $this->superAdmin->assignRole($this->superAdminRole);

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        DB::purge('mysql');
        parent::tearDown();
    }
}
