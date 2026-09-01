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

        $planId = SubscriptionPlan::query()->value('id');
        $this->assertNotNull($planId, 'A subscription plan is required for the Super Admin reconciliation scenario.');

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

            $this->assertSame(
                Tenant::query()->count(),
                $stats['total_tenants'],
            );
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
}
