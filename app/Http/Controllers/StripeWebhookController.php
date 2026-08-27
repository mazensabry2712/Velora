<?php

namespace App\Http\Controllers;

use App\Mail\PaymentFailedMail;
use App\Models\Tenant;
use App\Services\StripeService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        if ($this->isDuplicateWebhook('stripe', $event->id, $event->type)) {
            return response()->json(['status' => 'ok', 'duplicate' => true], 200);
        }

        try {
            match ($event->type) {
                'customer.subscription.created',
                'customer.subscription.updated'  => $this->handleSubscriptionUpdated($event),
                'customer.subscription.deleted'  => $this->handleSubscriptionDeleted($event),
                'invoice.paid'                   => $this->handleInvoicePaid($event),
                'invoice.payment_failed'         => $this->handlePaymentFailed($event),
                'checkout.session.completed'     => $this->handleCheckoutCompleted($event),
                default => null,
            };

            DB::table('webhook_events')
                ->where('provider', 'stripe')
                ->where('event_id', $event->id)
                ->update(['processed_at' => now()]);
        } catch (\Throwable $e) {
            DB::table('webhook_events')
                ->where('provider', 'stripe')
                ->where('event_id', $event->id)
                ->delete();

            Log::error('Stripe webhook handler error: ' . $e->getMessage(), [
                'event_type' => $event->type,
                'event_id'   => $event->id,
            ]);

            // Non-2xx tells Stripe to retry a transient/internal failure.
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    private function isDuplicateWebhook(string $provider, string $eventId, ?string $eventType = null): bool
    {
        try {
            DB::table('webhook_events')->insert([
                'provider'    => $provider,
                'event_id'    => $eventId,
                'event_type'  => $eventType,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return false;
        } catch (QueryException $e) {
            if (str_contains(strtolower($e->getMessage()), 'unique')) {
                Log::info('Duplicate webhook ignored', [
                    'provider' => $provider,
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                ]);

                return true;
            }

            throw $e;
        }
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

        if (!$tenantId) {
            Log::warning('Stripe payment_failed: no tenant_id in metadata', ['invoice' => $invoice->id]);
            return;
        }

        Log::warning("Payment failed for tenant {$tenantId}, invoice {$invoice->id}");

        $centralConn = config('tenancy.database.central_connection', 'mysql');

        $tenantRow    = DB::connection($centralConn)->table('tenants')->where('id', $tenantId)->first();
        $tenantData   = json_decode($tenantRow?->data ?? '{}', true);
        $ownerEmail   = $tenantData['email'] ?? null;
        $businessName = $tenantData['name'] ?? $tenantId;

        if (!$ownerEmail) {
            Log::warning("Stripe payment_failed: no email for tenant {$tenantId}");
            return;
        }

        $billingPortalUrl = 'https://' . (DB::connection($centralConn)->table('domains')->where('tenant_id', $tenantId)->value('domain') ?? 'app.velora.sa') . '/billing/expired';

        DB::connection($centralConn)
            ->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->update([
                'status'        => 'grace',
                'grace_ends_at' => now()->addDays(3),
                'updated_at'    => now(),
            ]);

        Mail::to($ownerEmail)
            ->queue(new PaymentFailedMail(
                businessName:     $businessName,
                ownerEmail:       $ownerEmail,
                invoiceId:        $invoice->id,
                billingPortalUrl: $billingPortalUrl,
                graceDays:        3,
            ));
    }

    private function handleCheckoutCompleted(\Stripe\Event $event): void
    {
        $session  = $event->data->object;
        $tenantId = $session->metadata['tenant_id'] ?? null;

        Log::info('Checkout completed for tenant: ' . ($tenantId ?? 'unknown'));

        if ($tenantId) {
            $centralConn = config('tenancy.database.central_connection', 'mysql');
            DB::connection($centralConn)
                ->table('tenant_subscriptions')
                ->where('tenant_id', $tenantId)
                ->whereNull('converted_at')
                ->update([
                    'converted_at' => now(),
                    'updated_at'   => now(),
                ]);
        }
    }
}
