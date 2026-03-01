<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Handle incoming Stripe webhook.
     * Route: POST /webhooks/stripe
     */
    public function handle(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        if (!$sigHeader) {
            Log::warning('Stripe webhook: missing signature header');
            return response()->json(['error' => 'Missing signature'], 400);
        }

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $sigHeader);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook: invalid signature — ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook: invalid payload — ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        Log::info('Stripe webhook received: ' . $event->type, ['event_id' => $event->id]);

        try {
            match ($event->type) {
                // Subscription becomes active (new or renewal)
                'customer.subscription.created',
                'customer.subscription.updated'  => $this->handleSubscriptionUpdated($event),

                // Subscription cancelled
                'customer.subscription.deleted'  => $this->handleSubscriptionDeleted($event),

                // Invoice paid (renewal)
                'invoice.paid'                   => $this->handleInvoicePaid($event),

                // Invoice payment failed
                'invoice.payment_failed'         => $this->handlePaymentFailed($event),

                // Checkout completed
                'checkout.session.completed'     => $this->handleCheckoutCompleted($event),

                default => null,
            };
        } catch (\Exception $e) {
            Log::error('Stripe webhook handler error: ' . $e->getMessage(), [
                'event_type' => $event->type,
                'event_id'   => $event->id,
            ]);
            // Return 200 to prevent Stripe from retrying on business logic errors
            return response()->json(['status' => 'error_logged'], 200);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    private function handleSubscriptionUpdated(\Stripe\Event $event): void
    {
        $subscription = $event->data->object;

        if (in_array($subscription->status, ['active', 'trialing'])) {
            $this->stripeService->handleSubscriptionActive($subscription);
        } elseif ($subscription->status === 'canceled') {
            $this->stripeService->handleSubscriptionCancelled($subscription);
        }
    }

    private function handleSubscriptionDeleted(\Stripe\Event $event): void
    {
        $this->stripeService->handleSubscriptionCancelled($event->data->object);
    }

    private function handleInvoicePaid(\Stripe\Event $event): void
    {
        $this->stripeService->handleInvoicePaid($event->data->object);
    }

    private function handlePaymentFailed(\Stripe\Event $event): void
    {
        $invoice  = $event->data->object;
        $tenantId = $invoice->subscription_details?->metadata['tenant_id'] ?? null;

        if ($tenantId) {
            Log::warning("Payment failed for tenant {$tenantId}, invoice {$invoice->id}");
            // Stripe handles Dunning automatically — no action needed here
            // Optionally: send custom email notification
        }
    }

    private function handleCheckoutCompleted(\Stripe\Event $event): void
    {
        $session = $event->data->object;
        // The subscription.created/updated event will handle activation
        // This is just for logging / CRM sync
        Log::info('Checkout completed for tenant: ' . ($session->metadata['tenant_id'] ?? 'unknown'));
    }
}
