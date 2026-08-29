<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantRegistrationService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class CompanyAdminDomainResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--database' => config('tenancy.database.central_connection', 'sqlite'),
            '--path' => 'database/migrations',
            '--force' => true,
        ]);
    }

    public function test_existing_active_tenant_returns_its_canonical_login_url(): void
    {
        $tenant = Tenant::create([
            'id' => 'resolver-active',
            'name' => 'Resolver Active',
            'email' => 'resolver-active@gmail.com',
            'active' => true,
        ]);

        $tenant->domains()->create([
            'domain' => 'resolver-active.'.config('app.base_domain', 'velora.test'),
        ]);

        $service = app(TenantRegistrationService::class);

        $result = $service->checkSubdomainAvailability('resolver-active');

        $this->assertFalse($result['available']);
        $this->assertSame(
            'http://resolver-active.'.config('app.base_domain', 'velora.test').'/login',
            $result['login_url']
        );
    }

    public function test_unknown_tenant_does_not_return_a_login_url(): void
    {
        $result = app(TenantRegistrationService::class)
            ->checkSubdomainAvailability('does-not-exist');

        $this->assertTrue($result['available']);
        $this->assertArrayNotHasKey('login_url', $result);
    }

    public function test_inactive_tenant_cannot_be_used_for_company_admin_login(): void
    {
        $tenant = Tenant::create([
            'id' => 'resolver-inactive',
            'name' => 'Resolver Inactive',
            'email' => 'resolver-inactive@gmail.com',
            'active' => false,
        ]);

        $tenant->domains()->create([
            'domain' => 'resolver-inactive.'.config('app.base_domain', 'velora.test'),
        ]);

        $result = app(TenantRegistrationService::class)
            ->checkSubdomainAvailability('resolver-inactive');

        $this->assertFalse($result['available']);
        $this->assertArrayNotHasKey('login_url', $result);
    }
}
