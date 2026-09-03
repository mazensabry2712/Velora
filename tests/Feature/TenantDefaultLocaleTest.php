<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\TenantProvisioningRequested;
use App\Jobs\FinalizeTenantProvisioning;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class TenantDefaultLocaleTest extends TestCase
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
        \DB::purge('tenant');

        if ($this->tenantDbPath !== null && file_exists($this->tenantDbPath)) {
            @unlink($this->tenantDbPath);
        }

        parent::tearDown();
    }

    public function test_signup_language_becomes_tenant_default_and_available_language(): void
    {
        Event::fake([TenantProvisioningRequested::class]);

        $plan = SubscriptionPlan::create([
            'name' => 'Locale Test Plan',
            'slug' => 'locale-test-' . bin2hex(random_bytes(4)),
            'description' => 'Tenant default locale test plan',
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

        $subdomain = 'locale-' . bin2hex(random_bytes(4));

        $response = $this->withServerVariables([
            'HTTP_HOST' => env('APP_DOMAIN', 'velora.test'),
            'SERVER_NAME' => env('APP_DOMAIN', 'velora.test'),
        ])->post('/signup', [
            'business_name' => 'Locale Test Business',
            'business_type' => 'Clinic',
            'subdomain' => $subdomain,
            'email' => 'locale-' . bin2hex(random_bytes(4)) . '@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'country' => 'US',
            'language' => 'fr',
            'terms' => '1',
            'plan_id' => $plan->id,
        ]);

        $response->assertRedirect();

        Event::assertDispatched(TenantProvisioningRequested::class);

        $tenant = Tenant::findOrFail($subdomain);
        $this->assertSame('fr', $tenant->language);
        $this->assertSame('queued', $tenant->provisioning_status);

        tenancy()->initialize($tenant);
        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->id],
            '--force' => true,
        ]);
        tenancy()->end();

        $this->tenantDbPath = database_path(
            config('tenancy.database.prefix', 'tenant').$tenant->id
        );

        (new FinalizeTenantProvisioning($tenant->refresh()))->handle();

        $tenantSettings = $tenant->run(
            fn () => Setting::query()->where('id', 1)->first()
        );

        $this->assertNotNull($tenantSettings);
        $this->assertSame('fr', $tenantSettings->language);

        $availableLanguages = is_string($tenantSettings->available_languages)
            ? json_decode($tenantSettings->available_languages, true)
            : $tenantSettings->available_languages;

        $this->assertIsArray($availableLanguages);
        $this->assertContains('fr', $availableLanguages);
    }
}
