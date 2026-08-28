<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Tenant\Actions\RegisterTenant;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TestCase;

final class SignupTenantHandoffTest extends TestCase
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
            'email' => 'handoff-' . $subdomain . '@gmail.com',
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

    private function performSignup(string $subdomain): \Illuminate\Testing\TestResponse
    {
        $plan = $this->createActivePlan();

        return $this
            ->withServerVariables([
                'HTTP_HOST' => $this->centralHost(),
                'SERVER_NAME' => $this->centralHost(),
            ])
            ->post('/signup', $this->validSignupData($subdomain) + [
                'plan_id' => $plan->id,
            ]);
    }

    public function test_registration_action_surfaces_real_signup_failure(): void
    {
        $plan = $this->createActivePlan();
        $subdomain = 'diag-' . substr(md5(uniqid('', true)), 0, 10);

        $result = app(RegisterTenant::class)->execute(
            $this->validSignupData($subdomain) + ['plan_id' => $plan->id]
        );

        $this->assertArrayHasKey('tenant', $result);
        $this->assertInstanceOf(Tenant::class, $result['tenant']);
        $this->assertSame($subdomain, $result['tenant']->getKey());
    }

    public function test_signup_http_persists_tenant_and_returns_redirect(): void
    {
        $subdomain = 'http-' . substr(md5(uniqid('', true)), 0, 10);
        $response = $this->performSignup($subdomain);

        $this->assertLessThan(500, $response->status(), $response->getContent());
        $this->assertTrue(Tenant::whereKey($subdomain)->exists(), $response->getContent());
        $response->assertRedirect();
    }

    public function test_signup_redirects_to_provisioning_page_for_async_setup(): void
    {
        $subdomain = 'handoff-' . substr(md5(uniqid('', true)), 0, 10);
        $response = $this->performSignup($subdomain);
        $response->assertRedirect();

        $tenant = Tenant::findOrFail($subdomain);
        $tenantDomain = $tenant->domains()->firstOrFail()->domain;

        $this->assertSame($subdomain . '.' . $this->centralHost(), $tenantDomain);
        $this->assertStringContainsString(
            'http://' . $this->centralHost() . '/signup/provisioning/',
            (string) $response->headers->get('Location')
        );
    }

    public function test_tenant_dashboard_route_is_registered(): void
    {
        $route = Route::getRoutes()->getByName('admin.dashboard');

        $this->assertNotNull($route);
        $this->assertSame('admin/dashboard', $route->uri());
    }

    public function test_tenant_domain_identification_works_for_the_created_domain(): void
    {
        $subdomain = 'probe-' . substr(md5(uniqid('', true)), 0, 10);
        $response = $this->performSignup($subdomain);
        $response->assertRedirect();

        $tenant = Tenant::findOrFail($subdomain);
        $tenantDomain = $tenant->domains()->firstOrFail()->domain;
        $probePath = '/__signup_tenant_probe_' . $subdomain;

        Route::middleware([
            'web',
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ])->get($probePath, function (Request $request) {
            return response()->json([
                'host' => $request->getHost(),
                'tenant_id' => tenant('id'),
            ]);
        });

        $probe = $this->get('http://' . $tenantDomain . $probePath);

        $this->assertSame(
            200,
            $probe->status(),
            "Tenant identification failed for {$tenantDomain}. Status={$probe->status()} Content=" . $probe->getContent()
        );

        $probe->assertJsonPath('host', $tenantDomain);
        $probe->assertJsonPath('tenant_id', $tenant->id);
    }

    public function test_signup_session_cookie_is_valid_for_the_tenant_subdomain(): void
    {
        $subdomain = 'cookie-' . substr(md5(uniqid('', true)), 0, 10);
        $response = $this->performSignup($subdomain);
        $response->assertRedirect();

        $sessionCookieName = config('session.cookie');
        $sessionCookie = collect($response->headers->getCookies())->first(
            fn ($cookie) => $cookie->getName() === $sessionCookieName
        );

        $this->assertNotNull($sessionCookie, 'Signup must issue the application session cookie.');
        $this->assertSame('.' . $this->centralHost(), $sessionCookie->getDomain());
    }

    public function test_unverified_tenant_is_not_authenticated_on_dashboard(): void
    {
        $subdomain = 'auth-' . substr(md5(uniqid('', true)), 0, 10);
        $response = $this->performSignup($subdomain);
        $response->assertRedirect();

        $tenant = Tenant::findOrFail($subdomain);
        $tenantDomain = $tenant->domains()->firstOrFail()->domain;

        $tenantResponse = $this->get('http://' . $tenantDomain . '/admin/dashboard');

        $this->assertSame(302, $tenantResponse->status());
        $this->assertSame('http://' . $tenantDomain . '/login', $tenantResponse->headers->get('Location'));
    }

    public function test_verified_email_while_provisioning_pending_keeps_user_on_provisioning_flow(): void
    {
        $subdomain = 'verify-' . substr(md5(uniqid('', true)), 0, 10);
        $response = $this->performSignup($subdomain);
        $response->assertRedirect();

        $tenant = Tenant::findOrFail($subdomain);
        $data = json_decode($tenant->getRawOriginal('data') ?? '{}', true) ?: [];
        $verificationToken = Crypt::decryptString((string) ($data['email_verification_token_encrypted'] ?? ''));

        $verifyResponse = $this->get('http://' . $this->centralHost() . '/email/verify/' . $verificationToken);

        $tenant = $tenant->fresh();
        $freshData = json_decode($tenant->getRawOriginal('data') ?? '{}', true) ?: [];

        $this->assertNotEmpty($freshData['email_verified_at'] ?? null);
        $this->assertEmpty($freshData['email_verification_token_hash'] ?? null);

        if (($tenant->provisioning_status ?? null) === 'ready') {
            $tenantDomain = $tenant->domains()->firstOrFail()->domain;
            $handoffToken = Crypt::decryptString((string) ($freshData['provisioning_token_encrypted'] ?? ''));

            $verifyResponse->assertRedirect(
                'http://' . $tenantDomain . '/__velora/provisioning/' . $handoffToken
            );
        } else {
            $verifyResponse->assertOk();
        }
    }
}
