<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Base test case for all SuperAdmin-scoped tests.
 *
 * Uses the central :memory: SQLite DB.  Central migrations are run via
 * migrate:fresh each setUp, then roles/users/settings tables are created
 * inline (those tables live in tenant migrations which would conflict if
 * run on the same connection as central migrations).
 *
 * The 'mysql' connection is redirected to a fresh SQLite :memory: so that
 * models with an explicit $connection='mysql' (e.g. UpgradeRequest) don't
 * attempt to connect to a real MySQL server during tests.
 */
abstract class SuperAdminTestCase extends TestCase
{
    protected User $superAdmin;
    protected Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $centralConn = config('tenancy.database.central_connection', 'sqlite');

        // Wipe and re-run central schema migrations (idempotent for :memory:)
        Artisan::call('migrate:fresh', [
            '--database' => $centralConn,
            '--force'    => true,
        ]);

        // ── Redirect 'mysql' → SQLite ─────────────────────────────────────
        // Models with $connection='mysql' (e.g. UpgradeRequest) would
        // otherwise try to connect to MySQL with ':memory:' as the DB name
        // (DB_DATABASE=:memory: is set in phpunit.xml for the sqlite driver).
        // We give them their own fresh :memory: SQLite instance instead.
        config(['database.connections.mysql' => config('database.connections.sqlite')]);
        DB::purge('mysql');

        // Create the tables that mysql-connection models need.
        Schema::connection('mysql')->create('upgrade_requests', function (Blueprint $table) {
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

        // ── Tenant-side tables needed by central model eager loading ──────
        // Tenant::users() and Tenant::settings() are hasMany/hasOne on the
        // default (sqlite) connection and need these tables to exist.

        // roles and users live in tenant migrations which would conflict
        // (duplicate cache/jobs tables) if run here. Create them directly.
        Schema::connection($centralConn)->create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('permissions')->nullable();
            $table->timestamps();
        });

        Schema::connection($centralConn)->create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('tenant_id')->nullable(); // for Tenant::users() eager load
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // settings is a tenant-side table; create a stub so Tenant::settings()
        // eager loading doesn't throw "no such table: settings".
        Schema::connection($centralConn)->create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->string('business_name')->nullable();
            $table->timestamps();
        });

        // Seed a Super Admin role + user for every test
        $this->superAdminRole = Role::create(['name' => 'Super Admin']);

        $this->superAdmin = User::create([
            'name'     => 'Super Admin User',
            'email'    => 'superadmin@velora.test',
            'password' => Hash::make('password'),
            'role_id'  => $this->superAdminRole->id,
        ]);

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        DB::purge('mysql'); // close the redirected :memory: connection
        parent::tearDown();
    }
}
