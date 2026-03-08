<?php

namespace Tests\Feature\SuperAdmin;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\SuperAdminTestCase;

#[Group('feature')]
#[Group('super-admin')]
#[Group('routes')]
class SuperAdminRoutesTest extends SuperAdminTestCase
{
    // ════════════════════════════════════════════════════════════════════════
    // Unauthenticated — all protected routes redirect to login
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function unauthenticated_request_to_dashboard_redirects_to_login(): void
    {
        $this->get(route('super-admin.dashboard'))
             ->assertRedirect(route('super-admin.login'));
    }

    #[Test]
    public function unauthenticated_request_to_analytics_redirects_to_login(): void
    {
        $this->get(route('super-admin.analytics'))
             ->assertRedirect(route('super-admin.login'));
    }

    #[Test]
    public function unauthenticated_request_to_promo_codes_redirects_to_login(): void
    {
        $this->get(route('super-admin.promo-codes.index'))
             ->assertRedirect(route('super-admin.login'));
    }

    // ════════════════════════════════════════════════════════════════════════
    // Login page
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function login_page_is_accessible_as_guest(): void
    {
        $this->get(route('super-admin.login'))
             ->assertOk();
    }

    #[Test]
    public function authenticated_super_admin_is_redirected_from_login_page(): void
    {
        $this->actingAs($this->superAdmin)
             ->get(route('super-admin.login'))
             ->assertRedirect(route('super-admin.dashboard'));
    }

    // ════════════════════════════════════════════════════════════════════════
    // Analytics page
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function super_admin_can_access_analytics(): void
    {
        $this->actingAs($this->superAdmin)
             ->get(route('super-admin.analytics'))
             ->assertOk();
    }

    #[Test]
    public function analytics_page_shows_kpis_tab_by_default(): void
    {
        $response = $this->actingAs($this->superAdmin)
                         ->get(route('super-admin.analytics'));

        $response->assertOk();
        $response->assertSee('kpis');
    }

    // ════════════════════════════════════════════════════════════════════════
    // Legacy redirects (reports + kpis → analytics)
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function legacy_reports_route_redirects_to_analytics(): void
    {
        $this->actingAs($this->superAdmin)
             ->get(route('super-admin.reports'))
             ->assertRedirect();
    }

    #[Test]
    public function legacy_reports_route_redirects_to_analytics_reports_tab(): void
    {
        $response = $this->actingAs($this->superAdmin)
                         ->get(route('super-admin.reports'));

        $response->assertRedirectContains('analytics');
    }

    #[Test]
    public function legacy_kpis_route_redirects_to_analytics(): void
    {
        $this->actingAs($this->superAdmin)
             ->get(route('super-admin.kpis'))
             ->assertRedirect();
    }

    #[Test]
    public function legacy_kpis_route_redirects_to_analytics_kpis_tab(): void
    {
        $response = $this->actingAs($this->superAdmin)
                         ->get(route('super-admin.kpis'));

        $response->assertRedirectContains('analytics');
    }

    // ════════════════════════════════════════════════════════════════════════
    // Other protected pages
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function super_admin_can_access_tenants_page(): void
    {
        $this->actingAs($this->superAdmin)
             ->get(route('super-admin.tenants'))
             ->assertOk();
    }

    #[Test]
    public function super_admin_can_access_subscription_plans_page(): void
    {
        $this->actingAs($this->superAdmin)
             ->get(route('super-admin.subscription-plans'))
             ->assertOk();
    }

    #[Test]
    public function super_admin_can_access_settings_page(): void
    {
        $this->actingAs($this->superAdmin)
             ->get(route('super-admin.settings'))
             ->assertOk();
    }

    #[Test]
    public function super_admin_can_access_notifications_page(): void
    {
        $this->actingAs($this->superAdmin)
             ->get(route('super-admin.notifications'))
             ->assertOk();
    }

    #[Test]
    public function super_admin_can_access_activity_logs_page(): void
    {
        $this->actingAs($this->superAdmin)
             ->get(route('super-admin.activity-logs'))
             ->assertOk();
    }
}
