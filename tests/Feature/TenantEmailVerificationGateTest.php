<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Tenant\Actions\RegisterTenant;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

final class TenantEmailVerificationGateTest extends TestCase
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

    private function createPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Email Gate Test Plan',
            'slug' => 'email-gate-'.substr(md5(uniqid('', true)), 0, 12),
            'description' => 'Email verification gate test plan',
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

    private function signup(string $subdomain, ?string $language = 'en'): Tenant
    {
        $plan = $this->createPlan();

        $data = [
            'business_name' => 'Email Gate Clinic',
            'business_type' => 'Clinic',
            'subdomain' => $subdomain,
            'email' => 'gate-'.$subdomain.'@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => 'US',
            'terms' => '1',
            'plan_id' => $plan->id,
        ];

        if ($language !== null) {
            $data['language'] = $language;
        }

        $result = app(RegisterTenant::class)->execute($data);

        return Tenant::findOrFail($result['tenant']->getKey());
    }

    public function test_signup_does_not_create_tenant_admin_before_email_verification(): void
    {
        $subdomain = 'gate-'.substr(md5(uniqid('', true)), 0, 10);
        $tenant = $this->signup($subdomain);
        $email = (string) $tenant->provisioning_email;

        $exists = $tenant->run(fn () => User::where('email', $email)->exists());

        $this->assertFalse($exists, 'Tenant admin must not exist before email verification.');
        $this->assertNull($tenant->email_verified_at);
    }

    public function test_signup_without_explicit_language_inherits_current_public_default(): void
    {
        SystemSetting::set('public_default_locale', 'fr', 'string', 'localization');

        $subdomain = 'gate-default-'.substr(md5(uniqid('', true)), 0, 8);
        $tenant = $this->signup($subdomain, null);

        $this->assertSame('fr', $tenant->language);
    }

    public function test_explicit_signup_language_becomes_tenant_default(): void
    {
        $subdomain = 'gate-lang-'.substr(md5(uniqid('', true)), 0, 8);
        $tenant = $this->signup($subdomain, 'de');

        $this->assertSame('de', $tenant->language);
    }

    public function test_verified_email_creates_the_admin_and_keeps_tenant_language(): void
    {
        $subdomain = 'gate-ok-'.substr(md5(uniqid('', true)), 0, 10);
        $tenant = $this->signup($subdomain, 'fr');
        $verificationToken = Crypt::decryptString((string) $tenant->email_verification_token_encrypted);

        $verify = $this->get('http://'.env('APP_DOMAIN', 'velora.test').'/email/verify/'.$verificationToken);
        $tenant = $tenant->fresh();

        $this->assertNotNull($tenant->email_verified_at);
        $this->assertNotNull($tenant->email_verification_token_used_at);

        $email = (string) $tenant->provisioning_email;
        $user = $tenant->run(fn () => User::where('email', $email)->first());
        $this->assertNotNull($user, 'Tenant admin must be created only after verification.');
        $this->assertSame('fr', $user->locale);

        if (($tenant->provisioning_status ?? null) === 'ready') {
            $verify->assertRedirect((string) $tenant->provisioning_redirect_url);
        } else {
            $verify->assertOk();
        }
    }
}
