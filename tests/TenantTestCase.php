<?php

namespace Tests;

use App\Models\Role;
use App\Models\Service;
use App\Models\Staff;
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
 * Each test runs inside tenant + central DB transactions that are rolled back
 * in tearDown so fixtures remain isolated without repeating the expensive
 * tenant migration setup.
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

    private static bool $migrationsDone = false;
    private static ?string $tenantId = null;
    private static ?array $fixtureIds = null;
    private static ?string $tenantDbPath = null;
    private bool $centralTransactionStarted = false;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$migrationsDone = false;
        self::$tenantId = null;
        self::$fixtureIds = null;
        self::$tenantDbPath = null;
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$tenantDbPath !== null && file_exists(self::$tenantDbPath)) {
            @unlink(self::$tenantDbPath);
        }

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

        $this->beginCentralTransaction();

        if (!self::$migrationsDone) {
            $this->bootstrapTenantOnce();
        } else {
            DB::table('tenants')->insert([
                'id' => self::$tenantId,
                'data' => json_encode(['name' => 'Test Clinic']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->tenant = Tenant::findOrFail(self::$tenantId);
            tenancy()->initialize($this->tenant);
        }

        $ids = self::$fixtureIds;
        $this->adminRole = Role::findOrFail($ids['adminRole']);
        $this->staffRole = Role::findOrFail($ids['staffRole']);
        $this->customerRole = Role::findOrFail($ids['customerRole']);
        $this->admin = User::findOrFail($ids['admin']);
        $this->staffMember = User::findOrFail($ids['staffMember']);
        $this->staff = Staff::findOrFail($ids['staff']);
        $this->customer = User::findOrFail($ids['customer']);
        $this->service = Service::findOrFail($ids['service']);

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        try {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        } finally {
            tenancy()->end();

            if ($this->centralTransactionStarted) {
                $central = DB::connection(config('tenancy.database.central_connection', 'sqlite'));
                if ($central->transactionLevel() > 0) {
                    $central->rollBack();
                }
                $this->centralTransactionStarted = false;
            }
        }

        parent::tearDown();
    }

    private function beginCentralTransaction(): void
    {
        $central = DB::connection(config('tenancy.database.central_connection', 'sqlite'));
        $central->beginTransaction();
        $this->centralTransactionStarted = true;
    }

    private function bootstrapTenantOnce(): void
    {
        Artisan::call('migrate', [
            '--database' => config('tenancy.database.central_connection', 'sqlite'),
            '--path' => 'database/migrations',
            '--force' => true,
        ]);

        $this->tenant = Tenant::create([
            'id' => 'test-tenant-' . uniqid(),
            'name' => 'Test Clinic',
        ]);

        self::$tenantId = $this->tenant->id;
        self::$tenantDbPath = database_path(
            config('tenancy.database.prefix', 'tenant') . self::$tenantId
        );

        tenancy()->initialize($this->tenant);
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

        self::$fixtureIds = [
            'adminRole' => $this->adminRole->id,
            'staffRole' => $this->staffRole->id,
            'customerRole' => $this->customerRole->id,
            'admin' => $this->admin->id,
            'staffMember' => $this->staffMember->id,
            'staff' => $this->staff->id,
            'customer' => $this->customer->id,
            'service' => $this->service->id,
        ];

        self::$migrationsDone = true;
    }
}
