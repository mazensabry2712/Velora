<?php

namespace Tests;

use App\Models\Role;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * Base test case for all tenant-scoped tests.
 *
 * Migrations run ONCE per class (not per test) to avoid memory exhaustion.
 * Each test runs inside a DB transaction that is rolled back in tearDown
 * so tests remain isolated without repeating the expensive migrate runs.
 */
abstract class TenantTestCase extends TestCase
{
    protected Tenant  $tenant;
    protected User    $admin;
    protected User    $staffMember;
    protected User    $customer;
    protected Service $service;
    protected Role    $adminRole;
    protected Role    $staffRole;
    protected Role    $customerRole;

    /** @var bool Has the class-level bootstrap already run? */
    private static bool $migrationsDone = false;

    /** @var string|null Tenant ID reused for all tests in the class */
    private static ?string $tenantId = null;

    /** @var int[]|null IDs of shared fixtures */
    private static ?array $fixtureIds = null;

    /** @var string|null Resolved tenant DB file path (set while app is alive) */
    private static ?string $tenantDbPath = null;

    // ── Class-level reset ─────────────────────────────────────────────────

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Reset flags for each test class run
        self::$migrationsDone = false;
        self::$tenantId       = null;
        self::$fixtureIds     = null;
        self::$tenantDbPath   = null;
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up the SQLite tenant DB file to prevent "already exists" errors on re-runs.
        // Use the pre-resolved path (set while app was alive) to avoid calling config()/database_path()
        // in static context after the Laravel app has been torn down.
        if (self::$tenantDbPath !== null && file_exists(self::$tenantDbPath)) {
            @unlink(self::$tenantDbPath);
        }

        parent::tearDownAfterClass();
    }

    // ── Per-test setup ────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();

        // Bypass tenant domain routing in HTTP tests
        $this->withoutMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            \App\Http\Middleware\EnsureSubscriptionIsValid::class,
            \App\Http\Middleware\RedirectIfOnboardingIncomplete::class,
        ]);

        if (!self::$migrationsDone) {
            $this->bootstrapTenantOnce();
        } else {
            // Central DB is :memory: and resets every test — re-migrate it
            Artisan::call('migrate', [
                '--database' => config('tenancy.database.central_connection', 'sqlite'),
                '--path'     => 'database/migrations',
                '--force'    => true,
            ]);

            // Re-insert the tenant record WITHOUT triggering the CreateDatabase observer
            // (the SQLite file already exists from the first run)
            \Illuminate\Support\Facades\DB::table('tenants')->insert([
                'id'         => self::$tenantId,
                'data'       => json_encode(['name' => 'Test Clinic']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Re-initialize tenancy for the existing tenant
            $this->tenant = Tenant::find(self::$tenantId);
            tenancy()->initialize($this->tenant);
        }

        // Reload fixture records into instance properties
        $ids = self::$fixtureIds;
        $this->adminRole    = Role::find($ids['adminRole']);
        $this->staffRole    = Role::find($ids['staffRole']);
        $this->customerRole = Role::find($ids['customerRole']);
        $this->admin        = User::find($ids['admin']);
        $this->staffMember  = User::find($ids['staffMember']);
        $this->customer     = User::find($ids['customer']);
        $this->service      = Service::find($ids['service']);

        // Begin a transaction so test-specific data is rolled back automatically
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        // Roll back any data created during this test
        DB::rollBack();
        tenancy()->end();
        \Illuminate\Database\Eloquent\Model::clearBootedModels();
        parent::tearDown();
    }

    // ── One-time migration + fixture creation ──────────────────────────────

    private function bootstrapTenantOnce(): void
    {
        // 1. Migrate central schema
        Artisan::call('migrate', [
            '--database' => config('tenancy.database.central_connection', 'sqlite'),
            '--path'     => 'database/migrations',
            '--force'    => true,
        ]);

        // 2. Create tenant record
        $this->tenant = Tenant::create([
            'id'   => 'test-tenant-' . uniqid(),
            'name' => 'Test Clinic',
        ]);
        self::$tenantId   = $this->tenant->id;
        // Resolve and store the DB path now while the app is fully booted
        self::$tenantDbPath = database_path(
            config('tenancy.database.prefix', 'tenant') . self::$tenantId
        );

        // 3. Switch context + migrate tenant DB
        tenancy()->initialize($this->tenant);
        Artisan::call('tenants:migrate', [
            '--tenants' => [$this->tenant->id],
            '--force'   => true,
        ]);

        // 4. Seed roles
        $this->adminRole    = Role::firstOrCreate(['name' => 'Admin Tenant']);
        $this->staffRole    = Role::firstOrCreate(['name' => 'Staff']);
        $this->customerRole = Role::firstOrCreate(['name' => 'Customer']);

        // 5. Create fixture users
        $this->admin = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@test.com',
            'password' => Hash::make('password'),
            'role_id'  => $this->adminRole->id,
        ]);

        $this->staffMember = User::create([
            'name'           => 'Staff Member',
            'email'          => 'staff@test.com',
            'password'       => Hash::make('password'),
            'role_id'        => $this->staffRole->id,
            'specialization' => 'General',
        ]);

        $this->customer = User::create([
            'name'     => 'Test Customer',
            'email'    => 'customer@test.com',
            'phone'    => '0501234567',
            'password' => Hash::make('password'),
            'role_id'  => $this->customerRole->id,
        ]);

        // 6. Create default service
        $this->service = Service::create([
            'name'      => 'Consultation',
            'name_ar'   => 'استشارة',
            'duration'  => 30,
            'price'     => 100.00,
            'is_active' => true,
        ]);

        // Store IDs for reuse across tests in this class
        self::$fixtureIds = [
            'adminRole'    => $this->adminRole->id,
            'staffRole'    => $this->staffRole->id,
            'customerRole' => $this->customerRole->id,
            'admin'        => $this->admin->id,
            'staffMember'  => $this->staffMember->id,
            'customer'     => $this->customer->id,
            'service'      => $this->service->id,
        ];

        self::$migrationsDone = true;
    }
}

