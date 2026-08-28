<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\TenantRegistrationService;
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

    private function uniqueId(string $prefix): string
    {
        return $prefix . '-' . bin2hex(random_bytes(4));
    }

    private function createActivePlan(): int
    {
        return SubscriptionPlan::create([
            'name' => $this->uniqueId('Resilience Plan'),
            'slug' => $this->uniqueId('resilience-plan'),
            'description' => 'Plan used by tenant registration resilience tests',
            'price' => 0,
            'billing_cycle' => 'monthly',
            'max_users' => null,
            'max_appointments' => null,
            'storage_limit' => null,
            'features' => [],
            'is_active' => true,
            'is_popular' => false,
            'trial_days' => 7,
        ])->id;
    }

    private function validSignupPayload(string $subdomain, int $planId): array
    {
        return [
            'business_name' => 'Resilience Test Business',
            'business_type' => 'Clinic',
            'subdomain' => $subdomain,
            'email' => $this->uniqueId('signup') . '@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'country' => 'US',
            'language' => 'en',
            'terms' => '1',
            'plan_id' => $planId,
        ];
    }

    private function postSignup(array $payload)
    {
        $host = (string) env('APP_DOMAIN', 'velora.test');

        return $this->withServerVariables([
            'HTTP_HOST' => $host,
            'SERVER_NAME' => $host,
        ])->post('/signup', $payload);
    }

    public function test_soft_deleted_tenant_id_is_treated_as_taken(): void
    {
        $subdomain = $this->uniqueId('softdel');

        $tenant = Tenant::create(['id' => $subdomain]);
        $tenant->delete();

        $result = app(TenantRegistrationService::class)
            ->checkSubdomainAvailability($subdomain);

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('already taken', strtolower($result['message']));
    }

    public function test_soft_deleted_tenant_id_rejects_registration_before_insert(): void
    {
        $subdomain = $this->uniqueId('blocked');
        $planId = $this->createActivePlan();

        $tenant = Tenant::create(['id' => $subdomain]);
        $tenant->delete();

        $response = $this->postSignup($this->validSignupPayload($subdomain, $planId));

        $response->assertRedirect();
        $errors = $response->getSession()->get('errors');

        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('subdomain'));
        $this->assertStringContainsString('already taken', strtolower($errors->first('subdomain')));
    }

    public function test_signup_subdomain_collision_does_not_become_generic_server_error(): void
    {
        $subdomain = $this->uniqueId('collision');
        $planId = $this->createActivePlan();

        Tenant::create(['id' => $subdomain]);

        $response = $this->postSignup($this->validSignupPayload($subdomain, $planId));

        $response->assertRedirect();
        $this->assertNotSame(500, $response->status());

        $errors = $response->getSession()->get('errors');
        $this->assertNotNull($errors);
        $this->assertTrue($errors->has('subdomain'));
        $this->assertStringContainsString('already taken', strtolower($errors->first('subdomain')));
    }
}
