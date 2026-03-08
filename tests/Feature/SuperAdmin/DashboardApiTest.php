<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\SuperAdminTestCase;

#[Group('feature')]
#[Group('super-admin')]
#[Group('dashboard-api')]
class DashboardApiTest extends SuperAdminTestCase
{
    // ════════════════════════════════════════════════════════════════════════
    // Auth guards
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/super-admin/dashboard')
             ->assertUnauthorized();
    }

    #[Test]
    public function non_super_admin_returns_403(): void
    {
        $regularRole = Role::create(['name' => 'Admin Tenant']);
        $regularUser = User::create([
            'name'     => 'Regular User',
            'email'    => 'regular@test.com',
            'password' => Hash::make('password'),
            'role_id'  => $regularRole->id,
        ]);

        $this->actingAs($regularUser)
             ->getJson('/api/super-admin/dashboard')
             ->assertForbidden();
    }

    // ════════════════════════════════════════════════════════════════════════
    // Dashboard stats endpoint
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function dashboard_returns_success_response(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/dashboard')
             ->assertOk()
             ->assertJsonPath('success', true);
    }

    #[Test]
    public function dashboard_response_contains_required_keys(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/dashboard')
             ->assertOk()
             ->assertJsonStructure([
                 'success',
                 'data' => [
                     'total_tenants',
                     'active_tenants',
                     'paid_tenants',
                     'trial_tenants',
                     'inactive_tenants',
                     'tenants_this_month',
                     'pending_upgrade_requests',
                     'recent_tenants',
                 ],
             ]);
    }

    #[Test]
    public function dashboard_total_tenants_is_zero_when_no_tenants(): void
    {
        $data = $this->actingAs($this->superAdmin)
                     ->getJson('/api/super-admin/dashboard')
                     ->assertOk()
                     ->json('data');

        $this->assertEquals(0, $data['total_tenants']);
        $this->assertEquals(0, $data['active_tenants']);
        $this->assertIsArray($data['recent_tenants']);
        $this->assertEmpty($data['recent_tenants']);
    }

    #[Test]
    public function dashboard_total_tenants_reflects_db_count(): void
    {
        DB::table('tenants')->insert([
            ['id' => 'tenant-api-1', 'data' => json_encode(['name' => 'Company A']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'tenant-api-2', 'data' => json_encode(['name' => 'Company B']), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $data = $this->actingAs($this->superAdmin)
                     ->getJson('/api/super-admin/dashboard')
                     ->assertOk()
                     ->json('data');

        $this->assertEquals(2, $data['total_tenants']);
    }

    #[Test]
    public function dashboard_recent_tenants_includes_name_and_is_active(): void
    {
        DB::table('tenants')->insert([
            'id'         => 'tenant-struct-1',
            'data'       => json_encode(['name' => 'Struct Co']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $recentTenants = $this->actingAs($this->superAdmin)
                              ->getJson('/api/super-admin/dashboard')
                              ->assertOk()
                              ->json('data.recent_tenants');

        $this->assertNotEmpty($recentTenants);
        $this->assertArrayHasKey('id', $recentTenants[0]);
        $this->assertArrayHasKey('name', $recentTenants[0]);
        $this->assertArrayHasKey('is_active', $recentTenants[0]);
        $this->assertArrayHasKey('created_at', $recentTenants[0]);
    }

    #[Test]
    public function dashboard_recent_tenants_returns_all_tenants(): void
    {
        $inserts = [];
        for ($i = 1; $i <= 15; $i++) {
            $inserts[] = [
                'id'         => "tenant-limit-{$i}",
                'data'       => json_encode(['name' => "Company {$i}"]),
                'created_at' => now()->subDays($i),
                'updated_at' => now()->subDays($i),
            ];
        }
        DB::table('tenants')->insert($inserts);

        $recentTenants = $this->actingAs($this->superAdmin)
                              ->getJson('/api/super-admin/dashboard')
                              ->assertOk()
                              ->json('data.recent_tenants');

        $this->assertCount(15, $recentTenants);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Tenants overview endpoint
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function tenants_overview_returns_success(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/dashboard/tenants-overview')
             ->assertOk()
             ->assertJsonPath('success', true)
             ->assertJsonStructure(['success', 'data']);
    }

    #[Test]
    public function tenants_overview_returns_all_tenants(): void
    {
        DB::table('tenants')->insert([
            ['id' => 'to-tenant-1', 'data' => json_encode(['name' => 'Overview Co 1']), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 'to-tenant-2', 'data' => json_encode(['name' => 'Overview Co 2']), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $data = $this->actingAs($this->superAdmin)
                     ->getJson('/api/super-admin/dashboard/tenants-overview')
                     ->assertOk()
                     ->json('data');

        $this->assertCount(2, $data);
    }

    // ════════════════════════════════════════════════════════════════════════
    // System stats endpoint
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function system_stats_returns_success(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/dashboard/system-stats')
             ->assertOk()
             ->assertJsonPath('success', true);
    }

    #[Test]
    public function system_stats_contains_required_keys(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/dashboard/system-stats')
             ->assertOk()
             ->assertJsonStructure([
                 'success',
                 'data' => [
                     'stats' => [
                         'total_tenants',
                         'active_tenants',
                         'tenants_this_month',
                         'tenants_today',
                     ],
                     'chart',
                 ],
             ]);
    }

    #[Test]
    public function system_stats_chart_has_30_days(): void
    {
        $chart = $this->actingAs($this->superAdmin)
                      ->getJson('/api/super-admin/dashboard/system-stats')
                      ->assertOk()
                      ->json('data.chart');

        $this->assertCount(30, $chart);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Subscription stats endpoint
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function subscription_stats_returns_success(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/dashboard/subscription-stats')
             ->assertOk()
             ->assertJsonPath('success', true);
    }

    #[Test]
    public function subscription_stats_contains_required_keys(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/dashboard/subscription-stats')
             ->assertOk()
             ->assertJsonStructure([
                 'success',
                 'data' => [
                     'plans',
                     'total_revenue',
                     'monthly_revenue',
                     'total_subscriptions',
                     'active_subscriptions',
                     'trial_subscriptions',
                 ],
             ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Activity summary endpoint
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function activity_summary_returns_success(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/dashboard/activity-summary')
             ->assertOk()
             ->assertJsonPath('success', true);
    }

    #[Test]
    public function activity_summary_contains_required_keys(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/dashboard/activity-summary')
             ->assertOk()
             ->assertJsonStructure([
                 'success',
                 'data' => [
                     'recent',
                     'today_count',
                     'week_count',
                 ],
             ]);
    }

    #[Test]
    public function activity_summary_today_count_is_zero_when_no_logs(): void
    {
        $data = $this->actingAs($this->superAdmin)
                     ->getJson('/api/super-admin/dashboard/activity-summary')
                     ->assertOk()
                     ->json('data');

        $this->assertEquals(0, $data['today_count']);
        $this->assertIsArray($data['recent']);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Growth metrics endpoint
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function growth_metrics_returns_success(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/dashboard/growth-metrics')
             ->assertOk()
             ->assertJsonPath('success', true);
    }

    #[Test]
    public function growth_metrics_returns_12_months(): void
    {
        $data = $this->actingAs($this->superAdmin)
                     ->getJson('/api/super-admin/dashboard/growth-metrics')
                     ->assertOk()
                     ->json('data');

        $this->assertCount(12, $data);
    }

    #[Test]
    public function growth_metrics_each_entry_has_required_keys(): void
    {
        $data = $this->actingAs($this->superAdmin)
                     ->getJson('/api/super-admin/dashboard/growth-metrics')
                     ->assertOk()
                     ->json('data');

        foreach ($data as $entry) {
            $this->assertArrayHasKey('month', $entry);
            $this->assertArrayHasKey('year', $entry);
            $this->assertArrayHasKey('tenants', $entry);
            $this->assertArrayHasKey('revenue', $entry);
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // Revenue metrics endpoint
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function revenue_metrics_returns_success(): void
    {
        $this->actingAs($this->superAdmin)
             ->getJson('/api/super-admin/dashboard/revenue-metrics')
             ->assertOk()
             ->assertJsonPath('success', true);
    }

    #[Test]
    public function revenue_metrics_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/super-admin/dashboard/revenue-metrics')
             ->assertUnauthorized();
    }
}
