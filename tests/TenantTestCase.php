<?php

namespace Tests;

use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * Base test case for all tenant-scoped tests.
 *
 * Migrations run ONCE per test class (not globally across all subclasses) to
 * avoid cross-class fixture leakage. Each test runs inside tenant + central
 * DB transactions that are rolled back in tearDown.
 */
abstract class TenantTestCase extends TestCase
{
    protected Tenant  $tenant;
    protected User    $admin;
    protected User    $staffMember;
    protected Staff   $staff;
    protected User    $customer;
    protected Service $service;
    protected Role    $adminRole;
    protected Role    $staffRole;
    protected Role    $customerRole;

    /** @var array<class-string, bool> */
    private static array $migrationsDone = [];

    /** @var array<class-string, string> */
    private static array $tenantIds = [];

    /** @var array<class-string, array<string, int|string>> */
    private static array $fixtureIds = [];

    /** @var array<class-string, string> */
    private static array $tenantDbPaths = [];

    private bool $centralTransactionStarted = false;
    private ?Connection $centralDatabaseConnection = null;
    private ?Connection $tenantDatabaseConnection = null;

    public static function tearDownAfterClass(): void
    {
        $class = static::class;
        $tenantDbPath = self::$tenantDbPaths[$class] ?? null;

        if ($tenantDbPath !== null && file_exists($tenantDbPath)) {
            @unlink($tenantDbPath);
        }

        unset(
            self::$migrationsDone[$class],
            self::$tenantIds[$class],
            self::$fixtureIds[$class],
            self::$tenantDbPaths[$class],
        );

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Bypass tenant-domain/subscription/onboarding infrastructure in
        // HTTP tests; authorization itself remains enabled and is tested.
        $this->withoutMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            \App\Http\Middleware\EnsureSubscriptionIsValid::class,
            \App\Http\Middleware\RedirectIfOnboardingIncomplete::class,
        ]);

        $class = static::class;

        if (! (self::$migrationsDone[$class] ?? false)) {
            $this->bootstrapTenantOnce();
        } else {
            $this->beginCentralTransaction();

            DB::connection(config('tenancy.database.central_connection', 'sqlite'))
                ->table('tenants')
                ->insert([
                    'id' => self::$tenantIds[$class],
                    'data' => json_encode(['name' => 'Test Clinic']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->tenant = Tenant::on($this->centralConnectionName())->findOrFail(self::$tenantIds[$class]);
            tenancy()->initialize($this->tenant);
        }

        $ids = self::$fixtureIds[$class];
        $this->adminRole = Role::findOrFail($ids['adminRole']);
        $this->staffRole = Role::findOrFail($ids['staffRole']);
        $this->customerRole = Role::findOrFail($ids['customerRole']);
        $this->admin = User::findOrFail($ids['admin']);
        $this->staffMember = User::findOrFail($ids['staffMember']);
        $this->staff = Staff::findOrFail($ids['staff']);
        $this->customer = User::findOrFail($ids['customer']);
        $this->service = Service::findOrFail($ids['service']);

        $this->tenantDatabaseConnection ??= DB::connection(DB::getDefaultConnection());
        $this->tenantDatabaseConnection->beginTransaction();
    }

    protected function tearDown(): void
    {
        try {
            if ($this->tenantDatabaseConnection?->transactionLevel() > 0) {
                $this->tenantDatabaseConnection->rollBack();
            }
        } finally {
            // Roll back using the connection object captured before tenancy
            // teardown. Dynamic Stancl connections may be removed at end().
            tenancy()->end();

            if ($this->centralTransactionStarted && $this->centralDatabaseConnection?->transactionLevel() > 0) {
                $this->centralDatabaseConnection->rollBack();
            }

            $this->centralTransactionStarted = false;
            $this->tenantDatabaseConnection = null;
            $this->centralDatabaseConnection = null;
        }

        parent::tearDown();
    }

    private function beginCentralTransaction(): void
    {
        $this->centralDatabaseConnection = DB::connection($this->centralConnectionName());
        $this->centralDatabaseConnection->beginTransaction();
        $this->centralTransactionStarted = true;
    }

    private function centralConnectionName(): string
    {
        return (string) config('tenancy.database.central_connection', config('database.default', 'sqlite'));
    }

    private function bootstrapTenantOnce(): void
    {
        $class = static::class;

        Artisan::call('migrate', [
            '--database' => $this->centralConnectionName(),
            '--path' => 'database/migrations',
            '--force' => true,
        ]);

        $this->beginCentralTransaction();

        $this->tenant = Tenant::on($this->centralConnectionName())->create([
            'id' => 'test-tenant-' . uniqid(),
            'name' => 'Test Clinic',
        ]);

        self::$tenantIds[$class] = $this->tenant->id;
        self::$tenantDbPaths[$class] = database_path(
            config('tenancy.database.prefix', 'tenant') . $this->tenant->id
        );

        tenancy()->initialize($this->tenant);
        $this->tenantDatabaseConnection = DB::connection(DB::getDefaultConnection());

        Artisan::call('tenants:migrate', [
            '--tenants' => [$this->tenant->id],
            '--force' => true,
        ]);

        $this->adminRole = Role::firstOrCreate(['name' => 'Admin Tenant']);
        $this->staffRole = Role::firstOrCreate(['name' => 'Staff']);
        $this->customerRole = Role::firstOrCreate(['name' => 'Customer']);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);
        $this->admin->assignRole($this->adminRole);

        $this->staffMember = User::create([
            'name' => 'Staff Member',
            'email' => 'staff@test.com',
            'password' => Hash::make('password'),
            'specialization' => 'General',
        ]);
        $this->staffMember->assignRole($this->staffRole);

        $this->staff = Staff::create([
            'user_id' => $this->staffMember->id,
            'first_name' => 'Staff',
            'last_name' => 'Member',
            'email' => $this->staffMember->email,
            'phone' => '0501234567',
            'accepts_bookings' => true,
            'is_active' => true,
        ]);

        $this->customer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '0501234567',
            'password' => Hash::make('password'),
        ]);
        $this->customer->assignRole($this->customerRole);

        $this->service = Service::create([
            'name' => 'Consultation',
            'name_ar' => 'استشارة',
            'duration' => 30,
            'price' => 100.00,
            'is_active' => true,
        ]);

        DB::table('staff_services')->insert([
            'user_id' => $this->staffMember->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::$fixtureIds[$class] = [
            'adminRole' => $this->adminRole->id,
            'staffRole' => $this->staffRole->id,
            'customerRole' => $this->customerRole->id,
            'admin' => $this->admin->id,
            'staffMember' => $this->staffMember->id,
            'staff' => $this->staff->id,
            'customer' => $this->customer->id,
            'service' => $this->service->id,
        ];

        self::$migrationsDone[$class] = true;
    }
}
