<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('qa')]
#[Group('master-scenario')]
#[Group('reconciliation')]
final class SuperAdminReconciliationScenarioTest extends TestCase
{
    #[Test]
    public function dashboard_tenant_and_subscription_counts_reconcile_with_central_database(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $tenantIds = [
            "qa-super-admin-a-{$suffix}",
            "qa-super-admin-b-{$suffix}",
            "qa-super-admin-c-{$suffix}",
        ];

        $planId = $this->ensureQaSubscriptionPlan()->id;

        try {
            DB::table('tenants')->insert([
                [
                    'id' => $tenantIds[0],
                    'data' => json_encode(['name' => 'QA Super Admin A', 'active' => true], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => $tenantIds[1],
                    'data' => json_encode(['name' => 'QA Super Admin B', 'active' => true], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => $tenantIds[2],
                    'data' => json_encode(['name' => 'QA Super Admin C', 'active' => false], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            TenantSubscription::query()->insert([
                [
                    'tenant_id' => $tenantIds[0],
                    'subscription_plan_id' => $planId,
                    'status' => 'active',
                    'amount_paid' => 100,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tenant_id' => $tenantIds[1],
                    'subscription_plan_id' => $planId,
                    'status' => 'trial',
                    'amount_paid' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $response = app(DashboardController::class)->index();
            $payload = $response->getData(true);

            $this->assertTrue($payload['success']);
            $stats = $payload['data'];

            $this->assertSame(Tenant::query()->count(), $stats['total_tenants']);
            $this->assertSame(
                Tenant::query()->get()->filter(fn (Tenant $tenant): bool => $tenant->active)->count(),
                $stats['active_tenants'],
            );
            $this->assertSame(
                TenantSubscription::query()->where('status', 'active')->distinct('tenant_id')->count('tenant_id'),
                $stats['paid_tenants'],
            );
            $this->assertSame(
                TenantSubscription::query()->where('status', 'trial')->distinct('tenant_id')->count('tenant_id'),
                $stats['trial_tenants'],
            );

            $recentIds = collect($stats['recent_tenants'])->pluck('id');
            foreach ($tenantIds as $tenantId) {
                $this->assertTrue($recentIds->contains($tenantId));
            }
        } finally {
            TenantSubscription::query()->whereIn('tenant_id', $tenantIds)->delete();
            DB::table('tenants')->whereIn('id', $tenantIds)->delete();
        }
    }

    #[Test]
    public function subscription_statistics_reconcile_revenue_and_plan_counts_with_central_database(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $tenantIds = [
            "qa-sub-stats-paid-{$suffix}",
            "qa-sub-stats-trial-{$suffix}",
        ];
        $planId = $this->ensureQaSubscriptionPlan()->id;

        try {
            DB::table('tenants')->insert([
                [
                    'id' => $tenantIds[0],
                    'data' => json_encode(['name' => 'QA Billing Paid', 'active' => true], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'id' => $tenantIds[1],
                    'data' => json_encode(['name' => 'QA Billing Trial', 'active' => true], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            TenantSubscription::query()->insert([
                [
                    'tenant_id' => $tenantIds[0],
                    'subscription_plan_id' => $planId,
                    'status' => 'active',
                    'amount_paid' => 125.50,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tenant_id' => $tenantIds[1],
                    'subscription_plan_id' => $planId,
                    'status' => 'trial',
                    'amount_paid' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $payload = app(DashboardController::class)->subscriptionStats()->getData(true);
            $data = $payload['data'];

            $this->assertTrue($payload['success']);
            $this->assertSame(
                (float) TenantSubscription::query()->where('status', 'active')->sum('amount_paid'),
                (float) $data['total_revenue'],
            );
            $this->assertSame(
                (float) TenantSubscription::query()->where('status', 'active')->whereMonth('created_at', now()->month)->sum('amount_paid'),
                (float) $data['monthly_revenue'],
            );
            $this->assertSame(TenantSubscription::query()->count(), $data['total_subscriptions']);
            $this->assertSame(TenantSubscription::query()->where('status', 'active')->count(), $data['active_subscriptions']);
            $this->assertSame(TenantSubscription::query()->where('status', 'trial')->count(), $data['trial_subscriptions']);

            $plan = collect($data['plans'])->firstWhere('id', $planId);
            $this->assertNotNull($plan);
            $this->assertSame(
                TenantSubscription::query()->where('subscription_plan_id', $planId)->where('status', 'active')->count(),
                (int) $plan['active_count'],
            );
            $this->assertSame(
                TenantSubscription::query()->where('subscription_plan_id', $planId)->where('status', 'trial')->count(),
                (int) $plan['trial_count'],
            );
        } finally {
            TenantSubscription::query()->whereIn('tenant_id', $tenantIds)->delete();
            DB::table('tenants')->whereIn('id', $tenantIds)->delete();
        }
    }

    private function ensureQaSubscriptionPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::query()->first()
            ?? SubscriptionPlan::query()->create([
                'name' => 'QA Basic',
                'slug' => 'qa-basic',
                'description' => 'Disposable plan for QA tests',
                'price' => 10,
                'billing_cycle' => 'monthly',
                'max_users' => 10,
                'max_appointments' => 100,
                'storage_limit' => 100,
                'features' => [],
                'is_active' => true,
                'is_popular' => false,
                'trial_days' => 7,
            ]);
    }
}
