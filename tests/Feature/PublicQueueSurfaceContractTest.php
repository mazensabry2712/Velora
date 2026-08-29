<?php

namespace Tests\Feature;

use App\Models\Setting;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class PublicQueueSurfaceContractTest extends TenantTestCase
{
    #[Test]
    public function queue_status_uses_tenant_branding_and_v3_surface(): void
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

        $response = $this->get(route('customer.queue.status'));

        $response->assertOk()
            ->assertSee('Rasha Clinic', false)
            ->assertSee('storage/logos/' . $this->tenant->id . '/rasha.svg', false)
            ->assertSee('velora-queue.css', false)
            ->assertSee('velora-queue-v3.js', false)
            ->assertSee('id="queueForm"', false)
            ->assertSee('id="lookup"', false)
            ->assertDontSee('<style>', false)
            ->assertDontSee('<script>function applyDarkMode', false)
            ->assertDontSee('vq-', false);
    }

    #[Test]
    public function queue_status_accepts_reference_in_query_string(): void
    {
        $response = $this->get(route('customer.queue.status', ['ref' => 'VL-NOT-FOUND']));

        $response->assertOk()
            ->assertSee('VL-NOT-FOUND', false)
            ->assertSee('Track appointment', false);
    }
}
