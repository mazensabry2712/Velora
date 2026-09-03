<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Services\StripeService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Stripe\Subscription;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
#[Group('billing')]
#[Group('reconciliation')]
final class StripeSubscriptionWebhookReconciliationTest extends TenantTestCase
{
    #[Test]
    public function different_stripe_events_for_the_same_subscription_are_not_treated_as_duplicates(): void
    {
        $planOne = $this->createPlan('Stripe Monthly', 'price_monthly_qa', 30.00, 'monthly');
        $planTwo = $this->createPlan('Stripe Yearly', 'price_yearly_qa', 300.00, 'yearly');

        $subscription = TenantSubscription::query()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $planOne->id,
            'status' => 'trial',
            'amount_paid' => 0,
        ]);

        $stripe = app(StripeService::class);

        $stripe->handleSubscriptionActive(
            $this->stripeSubscription('sub_qa_1', 'price_monthly_qa', 3000),
            'evt_qa_1',
        );

        $subscription->refresh();
        $this->assertSame($planOne->id, $subscription->subscription_plan_id);
        $this->assertSame('active', $subscription->status);
        $this->assertSame('evt_qa_1', $subscription->last_webhook_event);

        $stripe->handleSubscriptionActive(
            $this->stripeSubscription('sub_qa_1', 'price_yearly_qa', 30000),
            'evt_qa_2',
        );

        $subscription->refresh();
        $this->assertSame($planTwo->id, $subscription->subscription_plan_id);
        $this->assertSame('active', $subscription->status);
        $this->assertSame('evt_qa_2', $subscription->last_webhook_event);
        $this->assertSame('stripe', $subscription->payment_method);
    }

    #[Test]
    public function repeating_the_same_stripe_event_is_idempotent(): void
    {
        $plan = $this->createPlan('Stripe Idempotent', 'price_idempotent_qa', 50.00, 'monthly');

        $subscription = TenantSubscription::query()->create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'trial',
            'amount_paid' => 0,
        ]);

        $stripe = app(StripeService::class);
        $stripeSubscription = $this->stripeSubscription('sub_qa_2', 'price_idempotent_qa', 5000);

        $stripe->handleSubscriptionActive($stripeSubscription, 'evt_qa_same');
        $subscription->refresh();
        $firstEndsAt = $subscription->ends_at?->copy();

        $stripe->handleSubscriptionActive($stripeSubscription, 'evt_qa_same');
        $subscription->refresh();

        $this->assertSame('evt_qa_same', $subscription->last_webhook_event);
        $this->assertEquals($firstEndsAt, $subscription->ends_at);
    }

    private function createPlan(string $name, string $stripePriceId, float $price, string $billingCycle): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.bin2hex(random_bytes(3)),
            'price' => $price,
            'billing_cycle' => $billingCycle,
            'max_users' => 10,
            'max_appointments' => 100,
            'storage_limit' => 10,
            'features' => [],
            'is_active' => true,
            'is_popular' => false,
            'trial_days' => 0,
            'stripe_price_id' => $stripePriceId,
        ]);
    }

    private function stripeSubscription(string $id, string $priceId, int $unitAmount): Subscription
    {
        return Subscription::constructFrom([
            'id' => $id,
            'customer' => 'cus_qa',
            'status' => 'active',
            'metadata' => ['tenant_id' => $this->tenant->id],
            'items' => [
                'data' => [[
                    'price' => [
                        'id' => $priceId,
                        'unit_amount' => $unitAmount,
                    ],
                ]],
            ],
        ]);
    }
}
