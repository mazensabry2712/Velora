<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\Stripe;

use App\Mail\PaymentFailedMail;
use App\Services\StripeService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class StripeWebhookProcessor implements \App\Domain\Billing\Contracts\StripeWebhookProcessor
{
    public function __construct(private readonly StripeService $stripeService) {}

    public function process(string $payload, string $signature): array
    {
        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $signature);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            throw new \InvalidArgumentException('Invalid signature', 0, $e);
        } catch (\UnexpectedValueException $e) {
            throw new \InvalidArgumentException('Invalid payload', 0, $e);
        }

        Log::info('Stripe webhook received: ' . $event->type, ['event_id' => $event->id]);

        if ($this->isDuplicate($event->id, $event->type)) {
            return ['status' => 'ok', 'duplicate' => true];
        }

        try {
            match ($event->type) {
                'customer.subscription.created',
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
                'customer.subscription.deleted' => $this->stripeService->handleSubscriptionCancelled($event->data->object),
                'invoice.paid' => $this->stripeService->handleInvoicePaid($event->data->object),
                'invoice.payment_failed' => $this->handlePaymentFailed($event),
                'checkout.session.completed' => $this->handleCheckoutCompleted($event),
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
                'event_id' => $event->id,
            ]);
            throw $e;
        }

        return ['status' => 'ok'];
    }

    private function isDuplicate(string $eventId, ?string $eventType): bool
    {
        try {
            DB::table('webhook_events')->insert([
                'provider' => 'stripe',
                'event_id' => $eventId,
                'event_type' => $eventType,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return false;
        } catch (QueryException $e) {
            if (str_contains(strtolower($e->getMessage()), 'unique')) {
                Log::info('Duplicate webhook ignored', ['provider' => 'stripe', 'event_id' => $eventId]);
                return true;
            }
            throw $e;
        }
    }

    private function handleSubscriptionUpdated(\Stripe\Event $event): void
    {
        $subscription = $event->data->object;
        if (in_array($subscription->status, ['active', 'trialing'], true)) {
            $this->stripeService->handleSubscriptionActive($subscription, $event->id);
        } elseif ($subscription->status === 'canceled') {
            $this->stripeService->handleSubscriptionCancelled($subscription);
        }
    }

    private function handlePaymentFailed(\Stripe\Event $event): void
    {
        $invoice = $event->data->object;
        $tenantId = $invoice->subscription_details?->metadata['tenant_id'] ?? null;
        if (! $tenantId) {
            Log::warning('Stripe payment_failed: no tenant_id in metadata', ['invoice' => $invoice->id]);
            return;
        }

        $centralConn = (string) config('tenancy.database.central_connection', config('database.default', 'mysql'));
        $tenantRow = DB::connection($centralConn)->table('tenants')->where('id', $tenantId)->first();
        $tenantData = json_decode($tenantRow?->data ?? '{}', true);
        $ownerEmail = $tenantData['email'] ?? null;
        $businessName = $tenantData['name'] ?? $tenantId;
        if (! $ownerEmail) {
            Log::warning("Stripe payment_failed: no email for tenant {$tenantId}");
            return;
        }

        $billingPortalUrl = 'https://' . (DB::connection($centralConn)->table('domains')->where('tenant_id', $tenantId)->value('domain') ?? 'app.velora.sa') . '/billing/expired';
        DB::connection($centralConn)->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->update([
                'status' => 'grace',
                'grace_ends_at' => now()->addDays(3),
                'updated_at' => now(),
            ]);

        Mail::to($ownerEmail)->queue(new PaymentFailedMail(
            businessName: $businessName,
            ownerEmail: $ownerEmail,
            invoiceId: $invoice->id,
            billingPortalUrl: $billingPortalUrl,
            graceDays: 3,
        ));
    }

    private function handleCheckoutCompleted(\Stripe\Event $event): void
    {
        $session = $event->data->object;
        $tenantId = $session->metadata['tenant_id'] ?? null;
        if (! $tenantId) {
            return;
        }

        $centralConn = (string) config('tenancy.database.central_connection', config('database.default', 'mysql'));
        DB::connection($centralConn)->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->whereNull('converted_at')
            ->update(['converted_at' => now(), 'updated_at' => now()]);
    }
}
