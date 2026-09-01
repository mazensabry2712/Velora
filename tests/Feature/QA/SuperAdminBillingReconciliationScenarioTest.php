<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('qa')]
#[Group('master-scenario')]
#[Group('reconciliation')]
#[Group('billing')]
final class SuperAdminBillingReconciliationScenarioTest extends TestCase
{
    #[Test]
    public function subscription_statistics_and_revenue_match_central_subscription_records(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $tenantIds = [
            "qa-billing-recon-a-{$suffix}",
            "qa-billing-recon-b-{$suffix}",
        ];

        $plan = SubscriptionPlan::query()->firstOrFail();
        $now = now();

        try {
            DB::table('tenants')->insert([
                [
                    'id' => $tenantIds[0],
                    'data' => json_encode(['name' => 'QA Billing A', 'active' => true], JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'id' => $tenantIds[1],
                    'data' => json_encode(['name' => 'QA Billing B', 'active' => true], JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            TenantSubscription::query()->insert([
                [
                    'tenant_id' => $tenantIds[0],
                    'subscription_plan_id' => $plan->id,
                    'status' => 'active',
                    'amount_paid' => 120.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'tenant_id' => $tenantIds[1],
                    'subscription_plan_id' => $plan->id,
                    'status' => 'trial',
                    'amount_paid' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            $response = app(DashboardController::class)->subscriptionStats();
            $data = $response->getData(true)['data'];

            $this->assertSame(TenantSubscription::count(), $data['total_subscriptions']);
            $this->assertSame(
                TenantSubscription::where('status', 'active')->count(),
                $data['active_subscriptions'],
            );
            $this->assertSame(
                TenantSubscription::where('status', 'trial')->count(),
                $data['trial_subscriptions'],
            );
            $this->assertSame(
                (float) TenantSubscription::where('status', 'active')->sum('amount_paid'),
                (float) $data['total_revenue'],
            );
            $this->assertSame(
                (float) TenantSubscription::where('status', 'active')
                    ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                    ->sum('amount_paid'),
                (float) $data['monthly_revenue'],
            );

            $planStats = collect($data['plans'])->firstWhere('id', $plan->id);
            $this->assertNotNull($planStats);
            $this->assertSame(
                TenantSubscription::where('subscription_plan_id', $plan->id)->where('status', 'active')->count(),
                (int) $planStats['active_count'],
            );
            $this->assertSame(
                TenantSubscription::where('subscription_plan_id', $plan->id)->where('status', 'trial')->count(),
                (int) $planStats['trial_count'],
            );
        } finally {
            TenantSubscription::query()->whereIn('tenant_id', $tenantIds)->delete();
            DB::table('tenants')->whereIn('id', $tenantIds)->delete();
        }
    }
}
