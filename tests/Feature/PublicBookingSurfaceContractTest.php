<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class PublicBookingSurfaceContractTest extends TenantTestCase
{
    #[Test]
    public function booking_surface_uses_tenant_branding_and_only_tenant_languages(): void
    {
        Setting::updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'business_name' => 'Rasha Clinic',
                'logo' => 'logos/' . $this->tenant->id . '/rasha.svg',
                'language' => 'ar',
                'available_languages' => ['ar', 'en'],
            ],
        );

        $response = $this->get(route('customer.booking'));

        $response->assertOk();
        $response->assertSee('Rasha Clinic', false);
        $response->assertSee('storage/logos/' . $this->tenant->id . '/rasha.svg', false);
        $response->assertSee('value="ar"', false);
        $response->assertSee('value="en"', false);
        $response->assertSee('velora-booking.css', false);
        $response->assertSee('dark-mode-booking.js', false);
    }

    #[Test]
    public function unsupported_tenant_language_is_rejected(): void
    {
        Setting::updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'language' => 'ar',
                'available_languages' => ['ar', 'en'],
            ],
        );

        $response = $this->get(route('tenant.change.language', ['lang' => 'fr']));

        $response->assertRedirect();
    }
}
