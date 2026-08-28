<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
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

    private function host(): string
    {
        return (string) env('APP_DOMAIN', 'velora.test');
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

    private function signup(string $subdomain): \Illuminate\Testing\TestResponse
    {
        $plan = $this->createPlan();

        return $this
            ->withServerVariables([
                'HTTP_HOST' => $this->host(),
                'SERVER_NAME' => $this->host(),
            ])
            ->post('/signup', [
                'business_name' => 'Email Gate Clinic',
                'business_type' => 'Clinic',
                'subdomain' => $subdomain,
                'email' => 'gate-'.$subdomain.'@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'country' => 'US',
                'language' => 'en',
                'terms' => '1',
                'plan_id' => $plan->id,
            ]);
    }

    public function test_signup_does_not_create_tenant_admin_before_email_verification(): void
    {
        $subdomain = 'gate-'.substr(md5(uniqid('', true)), 0, 10);
        $response = $this->signup($subdomain);
        $response->assertRedirect();

        $tenant = Tenant::findOrFail($subdomain);
        $email = (string) $tenant->provisioning_email;

        $exists = $tenant->run(fn () => User::where('email', $email)->exists());

        $this->assertFalse($exists, 'Tenant admin must not exist before email verification.');
        $this->assertNull($tenant->email_verified_at);
    }

    public function test_verified_email_creates_the_admin_and_allows_one_time_handoff(): void
    {
        $subdomain = 'gate-ok-'.substr(md5(uniqid('', true)), 0, 10);
        $response = $this->signup($subdomain);
        $response->assertRedirect();

        $tenant = Tenant::findOrFail($subdomain);
        $verificationToken = Crypt::decryptString((string) $tenant->email_verification_token_encrypted);

        $verify = $this->get('http://'.$this->host().'/email/verify/'.$verificationToken);
        $tenant = $tenant->fresh();

        $this->assertNotNull($tenant->email_verified_at);
        $this->assertNotNull($tenant->email_verification_token_used_at);

        $email = (string) $tenant->provisioning_email;
        $userExists = $tenant->run(fn () => User::where('email', $email)->exists());
        $this->assertTrue($userExists, 'Tenant admin must be created only after verification.');

        if (($tenant->provisioning_status ?? null) === 'ready') {
            $verify->assertRedirect((string) $tenant->provisioning_redirect_url);
        } else {
            $verify->assertOk();
        }
    }
}
