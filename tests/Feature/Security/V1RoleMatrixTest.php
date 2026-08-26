<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\InitializeTenancyByToken;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('feature')]
#[Group('security')]
#[Group('authorization')]
class V1RoleMatrixTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // These tests exercise authorization itself; tenant selection is already
        // initialized by TenantTestCase.
        $this->withoutMiddleware(InitializeTenancyByToken::class);
    }

    #[Test]
    public function customer_cannot_manage_v1_appointments(): void
    {
        $this->actingAs($this->customer)
            ->postJson('/api/v1/appointments', [])
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function customer_cannot_manage_v1_queues(): void
    {
        $this->actingAs($this->customer)
            ->getJson('/api/v1/queues')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function staff_cannot_access_v1_invoices(): void
    {
        $this->actingAs($this->staffMember)
            ->getJson('/api/v1/invoices')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function customer_cannot_access_v1_analytics(): void
    {
        $this->actingAs($this->customer)
            ->getJson('/api/v1/analytics/summary')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function staff_can_access_v1_appointments_endpoint(): void
    {
        $this->actingAs($this->staffMember)
            ->getJson('/api/v1/appointments')
            ->assertOk();
    }
}
