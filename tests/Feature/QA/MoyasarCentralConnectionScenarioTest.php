<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Services\MoyasarService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
#[Group('billing')]
#[Group('reconciliation')]
final class MoyasarCentralConnectionScenarioTest extends TenantTestCase
{
    #[Test]
    public function activation_uses_the_central_subscription_connection_inside_tenant_context(): void
    {
        $plan = SubscriptionPlan::create([
            'name' => 'QA Central Connection Plan',
            'slug' => 'qa-central-connection-' . uniqid(),
            'price' => 25.00,
            'billing_cycle' => 'monthly',
            'max_users' => 10,
            'max_appointments' => 100,
            'storage_limit' => 100,
            'features' => [],
            'is_active' => true,
            'trial_days' => 0,
        ]);

        $subscription = TenantSubscription::query()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'trial',
            'amount_paid' => 0,
        ]);

        app(MoyasarService::class)->activateSubscription(
            $this->tenant->id,
            $plan->id,
            1250,
            'qa-moyasar-central-' . uniqid(),
        );

        $subscription->refresh();

        $this->assertSame('active', $subscription->status);
        $this->assertSame('moyasar', $subscription->payment_method);
        $this->assertSame('12.50', (string) $subscription->amount_paid);
        $this->assertSame($plan->id, $subscription->subscription_plan_id);
        $this->assertNotNull($subscription->starts_at);
        $this->assertNotNull($subscription->ends_at);
    }
}
