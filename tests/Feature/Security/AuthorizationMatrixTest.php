<?php

namespace Tests\Feature\Security;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('feature')]
#[Group('security')]
#[Group('authorization')]
class AuthorizationMatrixTest extends TenantTestCase
{
    #[Test]
    public function guest_is_rejected_from_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function customer_is_rejected_from_admin_dashboard(): void
    {
        $this->actingAs($this->customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    #[Test]
    public function staff_can_access_common_admin_dashboard(): void
    {
        $this->actingAs($this->staffMember)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    #[Test]
    public function staff_cannot_access_admin_only_assistants_page(): void
    {
        $this->actingAs($this->staffMember)
            ->get(route('admin.assistants'))
            ->assertForbidden();
    }

    #[Test]
    public function staff_cannot_access_admin_only_subscription_page(): void
    {
        $this->actingAs($this->staffMember)
            ->get(route('admin.subscription.index'))
            ->assertForbidden();
    }

    #[Test]
    public function customer_cannot_call_admin_appointment_api(): void
    {
        $this->actingAs($this->customer)
            ->postJson(route('admin.api.appointments.store'), [])
            ->assertForbidden();
    }

    #[Test]
    public function customer_cannot_call_admin_staff_api(): void
    {
        $this->actingAs($this->customer)
            ->getJson(route('admin.api.staff.show', $this->staffMember->id))
            ->assertForbidden();
    }
}
