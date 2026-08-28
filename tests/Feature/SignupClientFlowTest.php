<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Tenant\Actions\RegisterTenant;
use App\Mail\VerifyTenantEmailMail;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

final class SignupClientFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--database' => config('tenancy.database.central_connection', 'sqlite'),
            '--path' => 'database/migrations',
            '--force' => true,
        ]);

        Mail::fake();
    }

    private function centralHost(): string
    {
        return (string) env('APP_DOMAIN', 'velora.test');
    }

    private function uniqueSuffix(): string
    {
        return substr(strtolower(bin2hex(random_bytes(8))), 0, 12);
    }

    private function createActivePlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Signup Client Test Plan',
            'slug' => 'signup-client-' . $this->uniqueSuffix(),
            'description' => 'End-to-end signup coverage plan',
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

    private function validData(string $subdomain, ?string $language = 'ar'): array
    {
        return [
            'business_name' => 'Client Signup Clinic',
            'business_type' => 'Clinic',
            'subdomain' => $subdomain,
            'email' => 'signup-' . $subdomain . '@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => 'US',
            'language' => $language,
            'terms' => '1',
            'plan_id' => $this->createActivePlan()->id,
        ];
    }

    private function signup(string $subdomain, ?string $language = 'ar'): \Illuminate\Testing\TestResponse
    {
        return $this
            ->withServerVariables([
                'HTTP_HOST' => $this->centralHost(),
                'SERVER_NAME' => $this->centralHost(),
            ])
            ->post('/signup', $this->validData($subdomain, $language));
    }

    private function tenantFor(string $subdomain): Tenant
    {
        return Tenant::withTrashed()->whereKey($subdomain)->firstOrFail();
    }

    public function test_signup_page_is_rendered_for_default_and_non_default_locale(): void
    {
        $ar = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->get('/signup');

        $ar->assertOk();
        $ar->assertSee('id="signupForm"', false);
        $ar->assertSee('name="business_name"', false);
        $ar->assertSee('name="subdomain"', false);
        $ar->assertSee('name="email"', false);
        $ar->assertSee('name="password"', false);
        $ar->assertSee('name="terms"', false);
        $ar->assertSee('name="language"', false);

        $fr = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->get('/fr/signup');

        $fr->assertOk();
        $fr->assertSee('lang="fr"', false);
        $fr->assertSee('dir="ltr"', false);
    }

    public function test_successful_signup_creates_only_the_tenant_domain_and_verification_mail(): void
    {
        $subdomain = 'client-' . $this->uniqueSuffix();
        $response = $this->signup($subdomain, 'fr');

        $response->assertRedirect();

        $tenant = $this->tenantFor($subdomain);
        $domain = $tenant->domains()->firstOrFail()->domain;

        $this->assertSame('fr', $tenant->language);
        $this->assertSame($subdomain . '.' . $this->centralHost(), $domain);
        $this->assertNull($tenant->email_verified_at);
        $this->assertNull($tenant->email_verification_token_used_at);
        $this->assertNotNull($tenant->email_verification_url);
        $this->assertNotNull($tenant->email_verification_expires_at);

        $adminExists = $tenant->run(
            fn () => User::where('email', $tenant->provisioning_email)->exists()
        );
        $this->assertFalse($adminExists, 'Admin must not exist before email verification.');

        Mail::assertQueued(VerifyTenantEmailMail::class);
    }

    public function test_json_signup_returns_machine_readable_provisioning_contract(): void
    {
        $subdomain = 'json-' . $this->uniqueSuffix();
        $data = $this->validData($subdomain, 'de');

        $response = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->postJson('/signup', $data);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'provisioning_url',
                'message',
            ])
            ->assertJsonPath('success', true);

        $this->assertSame('de', $this->tenantFor($subdomain)->language);
    }

    public function test_signup_validation_rejects_missing_required_fields_without_creating_a_tenant(): void
    {
        $subdomain = 'missing-' . $this->uniqueSuffix();

        $response = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->post('/signup', [
            'subdomain' => $subdomain,
            'email' => 'missing-' . $this->uniqueSuffix() . '@gmail.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['business_name', 'password', 'terms']);
        $this->assertFalse(Tenant::whereKey($subdomain)->exists());
    }

    public function test_signup_rejects_password_mismatch_and_does_not_persist_password_in_session(): void
    {
        $subdomain = 'password-' . $this->uniqueSuffix();
        $data = $this->validData($subdomain);
        $data['password_confirmation'] = 'different-password';

        $response = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->post('/signup', $data);

        $response->assertRedirect()->assertSessionHasErrors(['password']);
        $this->assertFalse(Tenant::whereKey($subdomain)->exists());
        $this->assertNull(session('password'));
        $this->assertNull(session('password_confirmation'));
    }

    public function test_signup_rejects_terms_without_creating_a_tenant(): void
    {
        $subdomain = 'terms-' . $this->uniqueSuffix();
        $data = $this->validData($subdomain);
        unset($data['terms']);

        $response = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->post('/signup', $data);

        $response->assertRedirect()->assertSessionHasErrors(['terms']);
        $this->assertFalse(Tenant::whereKey($subdomain)->exists());
    }

    public function test_signup_rejects_invalid_subdomain_and_unsupported_language(): void
    {
        $badSubdomain = 'Bad_Name_' . $this->uniqueSuffix();
        $data = $this->validData($badSubdomain, 'fr');

        $response = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->post('/signup', $data);

        $response->assertRedirect()->assertSessionHasErrors(['subdomain']);
        $this->assertFalse(Tenant::whereKey(strtolower($badSubdomain))->exists());

        $validSubdomain = 'lang-' . $this->uniqueSuffix();
        $invalidLanguage = $this->validData($validSubdomain, 'xx');

        $response = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->post('/signup', $invalidLanguage);

        $response->assertRedirect()->assertSessionHasErrors(['language']);
        $this->assertFalse(Tenant::whereKey($validSubdomain)->exists());
    }

    public function test_duplicate_subdomain_and_duplicate_email_are_rejected_without_creating_a_second_tenant(): void
    {
        $subdomain = 'duplicate-' . $this->uniqueSuffix();
        $first = $this->signup($subdomain, 'ar');
        $first->assertRedirect();

        $firstTenant = $this->tenantFor($subdomain);
        $firstEmail = (string) $firstTenant->provisioning_email;

        $duplicateSubdomain = $this->validData($subdomain, 'en');
        $duplicateSubdomain['email'] = 'another-' . $this->uniqueSuffix() . '@gmail.com';

        $response = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->post('/signup', $duplicateSubdomain);

        $response->assertRedirect()->assertSessionHasErrors(['subdomain']);
        $this->assertSame(1, Tenant::whereKey($subdomain)->count());

        $duplicateEmail = $this->validData('email-' . $this->uniqueSuffix(), 'en');
        $duplicateEmail['email'] = $firstEmail;

        $response = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->post('/signup', $duplicateEmail);

        $response->assertRedirect()->assertSessionHasErrors(['email']);
        $this->assertFalse(Tenant::whereKey($duplicateEmail['subdomain'])->exists());
    }

    public function test_supported_signup_languages_become_the_tenant_default(): void
    {
        $locales = config('localizer.supported_locales', []);

        foreach ($locales as $locale) {
            $subdomain = 'l-' . $locale . '-' . substr($this->uniqueSuffix(), 0, 8);
            $tenant = $this->tenantFor($this->registerTenant($subdomain, $locale));

            $this->assertSame($locale, $tenant->language);
        }
    }

    public function test_signup_without_language_uses_the_configured_public_default(): void
    {
        \App\Models\SystemSetting::set('public_default_locale', 'fr', 'string', 'localization');

        $subdomain = 'default-' . $this->uniqueSuffix();
        $plan = $this->createActivePlan();
        $data = [
            'business_name' => 'Default Language Clinic',
            'business_type' => 'Clinic',
            'subdomain' => $subdomain,
            'email' => 'default-' . $subdomain . '@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => 'US',
            'terms' => '1',
            'plan_id' => $plan->id,
        ];

        $response = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->post('/signup', $data);

        $response->assertRedirect();
        $this->assertSame('fr', $this->tenantFor($subdomain)->language);
    }

    public function test_email_verification_is_single_use_and_creates_the_first_admin_with_tenant_language(): void
    {
        $subdomain = 'verify-' . $this->uniqueSuffix();
        $this->signup($subdomain, 'fr')->assertRedirect();

        $tenant = $this->tenantFor($subdomain);
        $token = Crypt::decryptString((string) $tenant->email_verification_token_encrypted);

        $verify = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->get('/email/verify/' . $token);

        $tenant = $tenant->fresh();
        $verify->assertSuccessful();

        $this->assertNotNull($tenant->email_verified_at);
        $this->assertNotNull($tenant->email_verification_token_used_at);
        $this->assertNull($tenant->email_verification_token_hash);
        $this->assertNull($tenant->email_verification_token_encrypted);
        $this->assertNull($tenant->email_verification_expires_at);

        $user = $tenant->run(
            fn () => User::where('email', $tenant->provisioning_email)->first()
        );

        $this->assertNotNull($user);
        $this->assertSame('fr', $user->locale);

        $replay = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->get('/email/verify/' . $token);

        $replay->assertNotFound();
    }

    public function test_expired_email_verification_token_is_rejected_and_does_not_create_admin(): void
    {
        $subdomain = 'expired-' . $this->uniqueSuffix();
        $this->signup($subdomain, 'de')->assertRedirect();

        $tenant = $this->tenantFor($subdomain);
        $token = Crypt::decryptString((string) $tenant->email_verification_token_encrypted);
        $tenant->forceFill(['email_verification_expires_at' => now()->subHour()])->save();

        $response = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->get('/email/verify/' . $token);

        $response->assertNotFound();
        $this->assertNull($tenant->fresh()->email_verified_at);
        $this->assertFalse(
            $tenant->run(fn () => User::where('email', $tenant->provisioning_email)->exists())
        );
    }

    public function test_expired_provisioning_link_is_rejected(): void
    {
        $subdomain = 'provision-' . $this->uniqueSuffix();
        $this->signup($subdomain, 'en')->assertRedirect();

        $tenant = $this->tenantFor($subdomain);
        $token = Crypt::decryptString((string) $tenant->provisioning_token_encrypted);
        $tenant->forceFill(['created_at' => now()->subMinutes(31)])->save();

        $response = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->get('/signup/provisioning/' . $token);

        $response->assertNotFound();
    }

    public function test_resend_verification_is_throttled_before_verification_and_idempotent_after_verification(): void
    {
        $subdomain = 'resend-' . $this->uniqueSuffix();
        $this->signup($subdomain, 'ar')->assertRedirect();

        $tenant = $this->tenantFor($subdomain);
        $token = Crypt::decryptString((string) $tenant->provisioning_token_encrypted);
        $key = 'tenant-email-verification:' . '127.0.0.1' . ':' . $tenant->id;

        for ($i = 0; $i < 3; $i++) {
            $response = $this->withServerVariables([
                'HTTP_HOST' => $this->centralHost(),
                'SERVER_NAME' => $this->centralHost(),
            ])->post('/signup/provisioning/' . $token . '/resend');

            $response->assertOk()->assertJsonPath('success', true);
        }

        $throttled = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->post('/signup/provisioning/' . $token . '/resend');

        $throttled->assertStatus(429);

        RateLimiter::clear($key);

        $verificationToken = Crypt::decryptString((string) $tenant->fresh()->email_verification_token_encrypted);
        $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->get('/email/verify/' . $verificationToken)->assertSuccessful();

        $verifiedResend = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->post('/signup/provisioning/' . $token . '/resend');

        $verifiedResend->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('verified', true);
    }

    public function test_nonexistent_tokens_are_not_oracle_endpoints(): void
    {
        $provisioning = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->get('/signup/provisioning/not-a-real-token');

        $verification = $this->withServerVariables([
            'HTTP_HOST' => $this->centralHost(),
            'SERVER_NAME' => $this->centralHost(),
        ])->get('/email/verify/not-a-real-token');

        $provisioning->assertNotFound();
        $verification->assertNotFound();
    }

    private function registerTenant(string $subdomain, string $locale): string
    {
        $result = app(RegisterTenant::class)->execute($this->validData($subdomain, $locale));

        return (string) $result['tenant']->getKey();
    }
}