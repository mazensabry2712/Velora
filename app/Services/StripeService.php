<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeService
{
    protected \Stripe\StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
    }

    private function centralConnection(): string
    {
        return (string) config('tenancy.database.central_connection', config('database.default'));
    }

    private function centralDb()
    {
        return DB::connection($this->centralConnection());
    }

    /**
     * Get or create a Stripe Customer for a tenant.
     */
    public function getOrCreateCustomer(string $tenantId, string $email, string $name): \Stripe\Customer
    {
        $subscription = $this->centralDb()->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->first();

        if ($subscription?->stripe_customer_id) {
            return $this->stripe->customers->retrieve($subscription->stripe_customer_id);
        }

        $customer = $this->stripe->customers->create([
            'email'    => $email,
            'name'     => $name,
            'metadata' => ['tenant_id' => $tenantId],
        ]);

        $this->centralDb()->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Create a Stripe Checkout Session for plan upgrade.
     */
    public function createCheckoutSession(
        string $tenantId,
        string $stripePriceId,
        string $customerEmail,
        string $customerName,
        string $successUrl,
        string $cancelUrl,
        array $metadata = []
    ): \Stripe\Checkout\Session {
        $customer = $this->getOrCreateCustomer($tenantId, $customerEmail, $customerName);

        return $this->stripe->checkout->sessions->create([
            'customer'   => $customer->id,
            'mode'       => 'subscription',
            'line_items' => [[
                'price'    => $stripePriceId,
                'quantity' => 1,
            ]],
            'success_url'   => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'    => $cancelUrl,
            'metadata'      => array_merge($metadata, ['tenant_id' => $tenantId]),
            'subscription_data' => [
                'metadata' => ['tenant_id' => $tenantId],
            ],
            'allow_promotion_codes' => true,
            'billing_address_collection' => 'auto',
        ]);
    }

    /**
     * Retrieve a Checkout Session.
     */
    public function retrieveCheckoutSession(string $sessionId): \Stripe\Checkout\Session
    {
        return $this->stripe->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription', 'payment_intent'],
        ]);
    }

    /**
     * Handle successful payment / subscription activation.
     *
     * The webhook event id is the idempotency key. Stripe may emit several
     * events for the same subscription, so the subscription id itself cannot
     * be used as the event key.
     */
    public function handleSubscriptionActive(\Stripe\Subscription $stripeSubscription, ?string $webhookEventId = null): void
    {
        $tenantId  = $stripeSubscription->metadata['tenant_id'] ?? null;
        $priceId   = $stripeSubscription->items->data[0]->price->id ?? null;
        $eventId   = $webhookEventId ?: ('stripe_subscription_' . $stripeSubscription->id);

        if (!$tenantId) {
            Log::warning('Stripe webhook: missing tenant_id in subscription metadata', [
                'subscription_id' => $stripeSubscription->id,
            ]);
            return;
        }

        $central = $this->centralDb();

        // Idempotency check: the Stripe Event ID belongs to one webhook delivery.
        $existing = $central->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->where('last_webhook_event', $eventId)
            ->exists();

        if ($existing) {
            return;
        }

        // Map Stripe price → local plan. Never activate against an arbitrary
        // fallback plan when the provider price is not configured locally.
        $plan = $central->table('subscription_plans')
            ->where('stripe_price_id', $priceId)
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            throw new \RuntimeException('Stripe webhook references an unknown or inactive local plan.');
        }

        $durationDays = $plan->billing_cycle === 'yearly' ? 365 : 30;
        $now = now();

        $central->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(1)
            ->update([
                'status'                  => 'active',
                'subscription_plan_id'    => $plan->id,
                'stripe_subscription_id'  => $stripeSubscription->id,
                'stripe_customer_id'      => $stripeSubscription->customer,
                'stripe_price_id'         => $priceId,
                'trial_ends_at'           => null,
                'grace_ends_at'           => null,
                'starts_at'               => $now,
                'ends_at'                 => $now->copy()->addDays($durationDays),
                'amount_paid'             => ($stripeSubscription->items->data[0]->price->unit_amount ?? 0) / 100,
                'payment_method'          => 'stripe',
                'last_webhook_event'      => $eventId,
                'updated_at'              => $now,
            ]);

        Log::info("Tenant {$tenantId} subscription activated via Stripe.");
    }

    /**
     * Handle subscription cancelled.
     */
    public function handleSubscriptionCancelled(\Stripe\Subscription $stripeSubscription): void
    {
        $tenantId = $stripeSubscription->metadata['tenant_id'] ?? null;
        if (!$tenantId) return;

        $this->centralDb()->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->where('stripe_subscription_id', $stripeSubscription->id)
            ->update([
                'status'        => 'cancelled',
                'cancelled_at'  => now(),
                'updated_at'    => now(),
            ]);
    }

    /**
     * Handle subscription renewal.
     */
    public function handleInvoicePaid(\Stripe\Invoice $invoice): void
    {
        $subscriptionId = $invoice->subscription;
        if (!$subscriptionId) return;

        $central = $this->centralDb();
        $sub = $central->table('tenant_subscriptions')
            ->where('stripe_subscription_id', $subscriptionId)
            ->first();

        if (!$sub) return;

        $plan = $central->table('subscription_plans')->find($sub->subscription_plan_id);
        $durationDays = $plan?->billing_cycle === 'yearly' ? 365 : 30;

        $central->table('tenant_subscriptions')
            ->where('id', $sub->id)
            ->update([
                'status'     => 'active',
                'ends_at'    => now()->addDays($durationDays),
                'updated_at' => now(),
            ]);
    }

    /**
     * Construct Stripe webhook event with signature verification.
     */
    public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        return \Stripe\Webhook::constructEvent(
            $payload,
            $sigHeader,
            config('services.stripe.webhook_secret')
        );
    }

    /**
     * Get billing portal session for a tenant.
     */
    public function createBillingPortalSession(string $customerId, string $returnUrl): \Stripe\BillingPortal\Session
    {
        return $this->stripe->billingPortal->sessions->create([
            'customer'   => $customerId,
            'return_url' => $returnUrl,
        ]);
    }

    /**
     * Issue a full refund for a PaymentIntent ID.
     */
    public function refund(string $paymentIntentId): \Stripe\Refund
    {
        return $this->stripe->refunds->create([
            'payment_intent' => $paymentIntentId,
        ]);
    }
}
