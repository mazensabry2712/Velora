<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TenantVerificationLocaleTest extends TestCase
{
    private ?string $tenantDbPath = null;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--database' => config('tenancy.database.central_connection', 'mysql'),
            '--path' => 'database/migrations',
            '--force' => true,
        ]);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        DB::purge('tenant');

        if ($this->tenantDbPath !== null && file_exists($this->tenantDbPath)) {
            @unlink($this->tenantDbPath);
        }

        parent::tearDown();
    }

    /** @return array{0: Tenant, 1: string} */
    private function createTenant(string $language): array
    {
        $tenantId = 'locale-'.strtolower($language).'-'.bin2hex(random_bytes(4));
        $token = $tenantId.'.'.str_repeat(strtolower($language) === 'ar' ? 'a' : 'b', 64);
        $email = $tenantId.'@example.com';

        $tenant = Tenant::withoutEvents(fn () => Tenant::create([
            'id' => $tenantId,
            'name' => ucfirst($language).' Business',
            'email' => $email,
            'provisioning_email' => $email,
            'provisioning_password' => Crypt::encryptString('password123'),
            'language' => $language,
            'provisioning_status' => 'ready',
            'provisioning_redirect_url' => '',
            'email_verification_token_hash' => hash('sha256', $token),
            'email_verification_expires_at' => now()->addHour(),
            'email_verification_token_used_at' => null,
            'email_verified_at' => null,
        ]));

        tenancy()->initialize($tenant);
        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->id],
            '--force' => true,
        ]);
        tenancy()->end();

        $this->tenantDbPath = database_path(
            config('tenancy.database.prefix', 'tenant').$tenantId
        );

        return [$tenant->refresh(), $token];
    }

    public function test_email_verification_page_uses_tenant_signup_language(): void
    {
        [$tenant, $token] = $this->createTenant('ar');

        $response = $this->get(route('tenant.email.verify', ['token' => $token]));

        $response->assertOk();
        $this->assertSame('ar', app()->getLocale());
        $response->assertSee('<html lang="ar" dir="rtl">', false);
        $response->assertSee('تم التحقق من البريد الإلكتروني', false);
        $this->assertNotNull($tenant->refresh()->email_verified_at);
    }

    public function test_explicit_supported_language_can_override_tenant_default_on_verification_page(): void
    {
        [$tenant, $token] = $this->createTenant('en');

        $response = $this->get(route('tenant.email.verify', [
            'token' => $token,
            'lang' => 'ar',
        ]));

        $response->assertOk();
        $this->assertSame('ar', app()->getLocale());
        $response->assertSee('<html lang="ar" dir="rtl">', false);
        $this->assertNotNull($tenant->refresh()->email_verified_at);
    }
}
