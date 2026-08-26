<?php

namespace Tests\Feature\Health;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('feature')]
#[Group('health')]
#[Group('smoke')]
class ApplicationSmokeTest extends TenantTestCase
{
    #[Test]
    public function critical_routes_are_registered(): void
    {
        $routeNames = [
            'customer.booking',
            'customer.queue.status',
            'login',
            'admin.dashboard',
            'admin.appointments',
            'admin.staff',
            'admin.settings',
            'admin.queue',
            'admin.customers',
            'admin.reports',
            'admin.assistants',
            'admin.subscription.index',
            'admin.subscription.billing',
            'admin.subscription.upgrade',
        ];

        foreach ($routeNames as $name) {
            $this->assertNotNull(
                Route::getRoutes()->getByName($name),
                "Critical route [$name] is not registered."
            );
        }
    }

    #[Test]
    public function public_booking_page_is_a_real_view(): void
    {
        $response = $this->get(route('customer.booking'));

        $response->assertOk();
        $response->assertViewIs('customer.booking');
    }

    #[Test]
    public function public_queue_page_is_a_real_view(): void
    {
        $response = $this->get(route('customer.queue.status'));

        $response->assertOk();
        $response->assertViewIs('customer.queue-status');
    }

    #[Test]
    public function authenticated_admin_smoke_path_reaches_dashboard(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewIs('admin.dashboard.index');
    }

    #[Test]
    public function authenticated_staff_smoke_path_reaches_dashboard(): void
    {
        $response = $this->actingAs($this->staffMember)
            ->get(route('admin.dashboard'));

        $response->assertOk();
    }
}
