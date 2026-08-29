<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\Tenant\Actions\RegisterTenant;
use App\Mail\TenantPasswordResetMail;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class TenantPasswordResetFlowTest extends TestCase
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

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Password Reset Test Plan',
            'slug' => 'password-reset-' . substr(md5(uniqid('', true)), 0, 12),
            'description' => 'Password reset flow tests',
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

    private function tenant(string $subdomain, string $locale = 'en'): Tenant
    {
        $result = app(RegisterTenant::class)->execute([
            'business_name' => 'Password Reset Clinic',
            'business_type' => 'Clinic',
            'subdomain' => $subdomain,
            'email' => $subdomain . '@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => 'US',
            'language' => $locale,
            'terms' => '1',
            'plan_id' => $this->plan()->id,
        ]);

        return Tenant::findOrFail($result['tenant']->getKey());
    }

    private function createVerifiedUser(Tenant $tenant, string $email, string $locale = 'en'): void
    {
        $tenant->run(function () use ($email, $locale): void {
            $user = new User();
            $user->fill([
                'name' => 'Reset User',
                'email' => $email,
                'password' => 'old-password',
                'locale' => $locale,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $saved = User::where('email', $email)->firstOrFail();
            self::assertNotNull($saved->email_verified_at);
        });
    }

    private function tokenFromResetUrl(string $resetUrl): string
    {
        $path = (string) parse_url($resetUrl, PHP_URL_PATH);
        $token = basename(trim($path, '/'));

        self::assertNotSame('', $token);

        return $token;
    }

    public function test_verified_user_can_request_and_complete_password_reset(): void
    {
        $subdomain = 'reset-' . substr(md5(uniqid('', true)), 0, 8);
        $email = 'owner-' . $subdomain . '@gmail.com';
        $tenant = $this->tenant($subdomain, 'fr');
        $this->createVerifiedUser($tenant, $email, 'fr');
        $host = $tenant->domains()->firstOrFail()->domain;
        $baseUrl = 'http://' . $host;

        Mail::fake();

        $requestReset = $this->post($baseUrl . '/forgot-password', ['email' => $email]);

        $requestReset->assertRedirect();
        $requestReset->assertSessionHas('status');

        $queued = null;
        Mail::assertQueued(TenantPasswordResetMail::class, function (TenantPasswordResetMail $mail) use ($email, $baseUrl, &$queued): bool {
            $queued = $mail;
            $query = [];
            parse_str((string) parse_url($mail->resetUrl, PHP_URL_QUERY), $query);

            return $mail->mailLocale === 'fr'
                && $mail->name === 'Reset User'
                && ($query['email'] ?? null) === $email
                && str_starts_with($mail->resetUrl, $baseUrl . '/reset-password/');
        });

        self::assertInstanceOf(TenantPasswordResetMail::class, $queued);
        $token = $this->tokenFromResetUrl($queued->resetUrl);

        $showReset = $this->get($baseUrl . '/reset-password/' . urlencode($token) . '?email=' . urlencode($email));
        $showReset->assertOk();

        $update = $this->post($baseUrl . '/reset-password/' . urlencode($token), [
            'email' => $email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $update->assertRedirect($baseUrl . '/login');
        $update->assertSessionHas('status');

        $tenant->run(function () use ($email): void {
            $user = User::where('email', $email)->firstOrFail();
            self::assertTrue(Hash::check('new-password-123', $user->password));
            self::assertFalse(Hash::check('old-password', $user->password));
            self::assertDatabaseMissing('password_reset_tokens', ['email' => $email]);
        });

        $reuse = $this->get($baseUrl . '/reset-password/' . urlencode($token) . '?email=' . urlencode($email));
        $reuse->assertRedirect();
        $reuse->assertSessionHasErrors('email');
    }

    public function test_unknown_email_does_not_disclose_account_existence(): void
    {
        $subdomain = 'reset-unknown-' . substr(md5(uniqid('', true)), 0, 8);
        $tenant = $this->tenant($subdomain);
        $host = $tenant->domains()->firstOrFail()->domain;

        Mail::fake();

        $response = $this->post('http://' . $host . '/forgot-password', ['email' => 'does-not-exist@gmail.com']);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Mail::assertNothingQueued();
    }

    public function test_reset_token_cannot_be_used_across_tenants(): void
    {
        $tenantA = $this->tenant('reset-a-' . substr(md5(uniqid('', true)), 0, 7));
        $tenantB = $this->tenant('reset-b-' . substr(md5(uniqid('', true)), 0, 7));

        $email = 'shared-reset@gmail.com';
        $this->createVerifiedUser($tenantA, $email);
        $this->createVerifiedUser($tenantB, $email);

        $hostA = $tenantA->domains()->firstOrFail()->domain;
        $hostB = $tenantB->domains()->firstOrFail()->domain;

        Mail::fake();

        $this->post('http://' . $hostA . '/forgot-password', ['email' => $email])->assertRedirect();

        $mail = null;
        Mail::assertQueued(TenantPasswordResetMail::class, function (TenantPasswordResetMail $queued) use (&$mail): bool {
            $mail = $queued;
            return true;
        });

        self::assertInstanceOf(TenantPasswordResetMail::class, $mail);
        $token = $this->tokenFromResetUrl($mail->resetUrl);

        $crossTenant = $this->get('http://' . $hostB . '/reset-password/' . urlencode($token) . '?email=' . urlencode($email));

        $crossTenant->assertRedirect();
        $crossTenant->assertSessionHasErrors('email');

        $tenantB->run(function () use ($email): void {
            $user = User::where('email', $email)->firstOrFail();
            self::assertTrue(Hash::check('old-password', $user->password));
        });
    }
}
