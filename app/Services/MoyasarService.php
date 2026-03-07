<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoyasarService
{
    private string $secretKey;
    private string $publishableKey;
    private string $baseUrl = 'https://api.moyasar.com/v1';

    public function __construct()
    {
        $this->secretKey      = config('services.moyasar.secret_key', '');
        $this->publishableKey = config('services.moyasar.publishable_key', '');
    }

    /**
     * Verify a payment via Moyasar API.
     * Returns payment data array.
     */
    public function verifyPayment(string $paymentId): array
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->get("{$this->baseUrl}/payments/{$paymentId}");

        if (!$response->ok()) {
            Log::error('Moyasar: failed to verify payment', [
                'payment_id' => $paymentId,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);
            throw new \RuntimeException('Moyasar payment verification failed.');
        }

        return $response->json();
    }

    /**
     * Activate tenant subscription after a confirmed Moyasar payment.
     */
    public function activateSubscription(
        string $tenantId,
        int    $planId,
        int    $amountHalalas,
        string $paymentId
    ): void {
        $eventKey = 'moyasar_' . $paymentId;

        // Idempotency – skip if already processed
        $exists = DB::table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->where('last_webhook_event', $eventKey)
            ->exists();

        if ($exists) {
            Log::info("Moyasar: duplicate callback ignored for payment {$paymentId}");
            return;
        }

        $plan         = DB::table('subscription_plans')->find($planId);
        $durationDays = ($plan?->billing_cycle === 'yearly') ? 365 : 30;
        $now          = now();

        DB::table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(1)
            ->update([
                'status'               => 'active',
                'subscription_plan_id' => $planId,
                'starts_at'            => $now,
                'ends_at'              => $now->copy()->addDays($durationDays),
                'billing_cycle'        => $plan?->billing_cycle ?? 'monthly',
                'amount_paid'          => round($amountHalalas / 100, 2),
                'payment_method'       => 'moyasar',
                'last_webhook_event'   => $eventKey,
                'trial_ends_at'        => null,
                'grace_ends_at'        => null,
                'converted_at'         => $now,
                'updated_at'           => $now,
            ]);

        Log::info("Moyasar: tenant {$tenantId} subscription activated. Plan ID={$planId}, Amount=" . round($amountHalalas / 100, 2) . ' SAR');
    }

    public function getPublishableKey(): string
    {
        return $this->publishableKey;
    }
}
