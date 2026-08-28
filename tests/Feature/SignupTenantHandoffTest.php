<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Tests\TestCase;

final class SignupTenantHandoffTest extends TestCase
{
    private function centralHost(): string
    {
        return (string) env('APP_DOMAIN', 'velora.test');
    }

    private function validSignupData(string $subdomain): array
    {
        return [
            'business_name' => 'Handoff Test Clinic',
            'business_type' => 'Clinic',
            'subdomain' => $subdomain,
            'email' => 'handoff-' . $subdomain . '@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => 'US',
            'language' => 'en',
            'terms' => '1',
        ];
    }

    private function createActivePlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Handoff Test Plan',
            'slug' => 'handoff-test-' . substr(md5(uniqid('', true)), 0, 12),
            'description' => 'Signup handoff test plan',
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
    }

    public function test_signup_redirects_to_the_created_tenant_dashboard(): void
    {
        $plan = $this->createActivePlan();
        $subdomain = 'handoff-' . substr(md5(uniqid('', true)), 0, 10);

        $response = $this
            ->withServerVariables([
                'HTTP_HOST' => $this->centralHost(),
                'SERVER_NAME' => $this->centralHost(),
            ])
            ->post('/signup', $this->validSignupData($subdomain) + [
                'plan_id' => $plan->id,
            ]);

        $response->assertRedirect();

        $tenant = Tenant::find($subdomain);
        $this->assertNotNull($tenant);

        $tenantDomain = $tenant->domains()->firstOrFail()->domain;
        $this->assertSame($subdomain . '.' . $this->centralHost(), $tenantDomain);
        $this->assertSame('http://' . $tenantDomain . '/admin/dashboard', $response->headers->get('Location'));
    }

    public function test_signup_session_cookie_is_valid_for_the_tenant_subdomain(): void
    {
        $plan = $this->createActivePlan();
        $subdomain = 'cookie-' . substr(md5(uniqid('', true)), 0, 10);

        $response = $this
            ->withServerVariables([
                'HTTP_HOST' => $this->centralHost(),
                'SERVER_NAME' => $this->centralHost(),
            ])
            ->post('/signup', $this->validSignupData($subdomain) + [
                'plan_id' => $plan->id,
            ]);

        $response->assertRedirect();

        $sessionCookieName = config('session.cookie');
        $cookies = $response->headers->getCookies();
        $sessionCookie = collect($cookies)->first(
            fn ($cookie) => $cookie->getName() === $sessionCookieName
        );

        $this->assertNotNull($sessionCookie, 'Signup must issue the application session cookie.');
        $this->assertSame('.' . $this->centralHost(), $sessionCookie->getDomain());

        $tenant = Tenant::findOrFail($subdomain);
        $tenantDomain = $tenant->domains()->firstOrFail()->domain;

        $this->assertStringEndsWith('.' . $this->centralHost(), $tenantDomain);
        $this->assertTrue(
            $sessionCookie->getDomain() === null ||
            str_ends_with($tenantDomain, ltrim($sessionCookie->getDomain(), '.'))
        );
    }

    public function test_signup_creates_an_authenticated_admin_in_the_created_tenant(): void
    {
        $plan = $this->createActivePlan();
        $subdomain = 'auth-' . substr(md5(uniqid('', true)), 0, 10);

        $response = $this
            ->withServerVariables([
                'HTTP_HOST' => $this->centralHost(),
                'SERVER_NAME' => $this->centralHost(),
            ])
            ->post('/signup', $this->validSignupData($subdomain) + [
                'plan_id' => $plan->id,
            ]);

        $response->assertRedirect();

        $tenant = Tenant::findOrFail($subdomain);
        $tenantDomain = $tenant->domains()->firstOrFail()->domain;
        $sessionCookie = collect($response->headers->getCookies())->first(
            fn ($cookie) => $cookie->getName() === config('session.cookie')
        );

        $this->assertNotNull($sessionCookie);

        $tenantResponse = $this
            ->withServerVariables([
                'HTTP_HOST' => $tenantDomain,
                'SERVER_NAME' => $tenantDomain,
            ])
            ->withCookie($sessionCookie->getName(), $sessionCookie->getValue())
            ->get('/admin/dashboard');

        $tenantResponse->assertSuccessful();
        $this->assertNotSame('/login', parse_url($tenantResponse->headers->get('Location', ''), PHP_URL_PATH));
    }
}
