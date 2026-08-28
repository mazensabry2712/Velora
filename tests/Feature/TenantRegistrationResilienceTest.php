<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\TenantRegistrationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class TenantRegistrationResilienceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--database' => config('tenancy.database.central_connection', 'sqlite'),
            '--path' => 'database/migrations',
            '--force' => true,
        ]);
    }

    public function test_soft_deleted_tenant_id_is_treated_as_taken(): void
    {
        $tenant = Tenant::create(['id' => 'reserved-soft-deleted']);
        $tenant->delete();

        $service = app(TenantRegistrationService::class);

        $result = $service->checkSubdomainAvailability('reserved-soft-deleted');

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('already taken', strtolower($result['message']));
    }

    public function test_soft_deleted_tenant_id_rejects_registration_before_insert(): void
    {
        $tenant = Tenant::create(['id' => 'reusable-blocked']);
        $tenant->delete();

        SubscriptionPlan::create([
            'name' => 'Resilience Plan',
            'slug' => 'resilience-plan',
            'description' => 'Tenant registration resilience test plan',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'max_users' => null,
            'max_appointments' => null,
            'storage_limit' => null,
            'features' => [],
            'is_active' => true,
            'is_popular' => false,
            'trial_days' => 7,
        ]);

        $this->expectException(ValidationException::class);
        $this->post('/signup', [
            'business_name' => 'Blocked Test Business',
            'business_type' => 'Clinic',
            'subdomain' => 'reusable-blocked',
            'email' => 'blocked-registration@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'country' => 'US',
            'language' => 'en',
            'terms' => '1',
        ]);
    }

    public function test_signup_subdomain_collision_does_not_become_generic_server_error(): void
    {
        Tenant::create(['id' => 'collision-check']);

        $response = $this->post('/signup', [
            'business_name' => 'Collision Test Business',
            'business_type' => 'Clinic',
            'subdomain' => 'collision-check',
            'email' => 'collision-check@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'country' => 'US',
            'language' => 'en',
            'terms' => '1',
        ]);

        $response->assertRedirect();
        $this->assertTrue($response->getSession()->get('errors')->has('subdomain'));
    }
}
