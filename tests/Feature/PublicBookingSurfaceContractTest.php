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

    #[Test]
    public function public_service_payload_exposes_only_booking_fields(): void
    {
        $this->service->update([
            'is_online_bookable' => true,
            'metadata' => ['internal' => 'must-not-leak'],
            'deposit_amount' => 25,
            'deposit_pct' => 25,
        ]);

        $response = $this->getJson('/api/booking/services');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'name_ar',
                    'duration',
                    'duration_minutes',
                    'price',
                    'description',
                ]],
            ]);

        $service = collect($response->json('data'))->firstWhere('id', $this->service->id);
        $this->assertNotNull($service);
        $this->assertArrayNotHasKey('metadata', $service);
        $this->assertArrayNotHasKey('deposit_amount', $service);
        $this->assertArrayNotHasKey('deposit_pct', $service);
    }

    #[Test]
    public function public_staff_payload_does_not_expose_staff_email(): void
    {
        $this->service->update(['is_online_bookable' => true]);

        $response = $this->getJson('/api/booking/staff/by-service/' . $this->service->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'staff_id',
                    'name',
                ]],
            ]);

        $staff = collect($response->json('data'))->firstWhere('staff_id', $this->staff->id);
        $this->assertNotNull($staff);
        $this->assertArrayNotHasKey('email', $staff);
    }
}
