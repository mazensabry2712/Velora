<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantVerificationLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_page_uses_tenant_signup_language(): void
    {
        $tenantId = 'locale-ar-'.bin2hex(random_bytes(4));
        $token = $tenantId.'.'.str_repeat('a', 64);

        $tenant = Tenant::withoutEvents(fn () => Tenant::create([
            'id' => $tenantId,
            'name' => 'Arabic Business',
            'language' => 'ar',
            'provisioning_status' => 'ready',
            'provisioning_redirect_url' => '',
            'email_verification_token_hash' => hash('sha256', $token),
            'email_verification_expires_at' => now()->addHour(),
            'email_verification_token_used_at' => null,
            'email_verified_at' => null,
        ]));

        $response = $this->get(route('tenant.email.verify', ['token' => $token]));

        $response->assertOk();
        $this->assertSame('ar', app()->getLocale());
        $response->assertSee('<html lang="ar" dir="rtl">', false);
        $response->assertSee('تم التحقق من البريد الإلكتروني', false);
        $this->assertNotNull($tenant->refresh()->email_verified_at);
    }

    public function test_explicit_supported_language_can_override_tenant_default_on_verification_page(): void
    {
        $tenantId = 'locale-en-'.bin2hex(random_bytes(4));
        $token = $tenantId.'.'.str_repeat('b', 64);

        Tenant::withoutEvents(fn () => Tenant::create([
            'id' => $tenantId,
            'name' => 'English Business',
            'language' => 'en',
            'provisioning_status' => 'ready',
            'provisioning_redirect_url' => '',
            'email_verification_token_hash' => hash('sha256', $token),
            'email_verification_expires_at' => now()->addHour(),
            'email_verification_token_used_at' => null,
            'email_verified_at' => null,
        ]));

        $response = $this->get(route('tenant.email.verify', [
            'token' => $token,
            'lang' => 'ar',
        ]));

        $response->assertOk();
        $this->assertSame('ar', app()->getLocale());
        $response->assertSee('<html lang="ar" dir="rtl">', false);
    }
}
