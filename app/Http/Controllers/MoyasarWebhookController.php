<?php

namespace App\Http\Controllers;

use App\Services\MoyasarService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MoyasarWebhookController extends Controller
{
    public function __construct(
        protected MoyasarService $moyasarService
    ) {}

    /**
     * Handle Moyasar server-to-server webhook (payment.paid / payment.failed).
     * Route: POST /webhooks/moyasar
     */
    public function handle(Request $request)
    {
        $secret = config('services.moyasar.webhook_secret');

        // Verify HMAC signature when secret is configured.
        if ($secret) {
            $signature = $request->header('X-Moyasar-Signature');
            $expected  = hash_hmac('sha256', $request->getContent(), $secret);

            if (!hash_equals($expected, (string) $signature)) {
                Log::warning('Moyasar webhook: invalid signature');
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        $payload = $request->json()->all();
        $type    = $payload['type'] ?? null;
        $data    = $payload['data'] ?? [];
        $eventId = (string) ($payload['id'] ?? $data['id'] ?? hash('sha256', $request->getContent()));

        Log::info('Moyasar webhook received', [
            'type' => $type,
            'event_id' => $eventId,
            'payment_id' => $data['id'] ?? null,
        ]);

        if ($this->isDuplicateWebhook('moyasar', $eventId, $type)) {
            return response()->json(['status' => 'ok', 'duplicate' => true], 200);
        }

        try {
            if ($type === 'payment.paid') {
                $this->handlePaymentPaid($data);
            } elseif ($type === 'payment.failed') {
                Log::warning('Moyasar payment failed', ['payment_id' => $data['id'] ?? null]);
            }

            DB::table('webhook_events')
                ->where('provider', 'moyasar')
                ->where('event_id', $eventId)
                ->update(['processed_at' => now()]);
        } catch (\Throwable $e) {
            DB::table('webhook_events')
                ->where('provider', 'moyasar')
                ->where('event_id', $eventId)
                ->delete();

            Log::error('Moyasar webhook handler error: ' . $e->getMessage(), [
                'event_id' => $eventId,
                'type' => $type,
                'payment_id' => $data['id'] ?? null,
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    private function isDuplicateWebhook(string $provider, string $eventId, ?string $eventType = null): bool
    {
        try {
            DB::table('webhook_events')->insert([
                'provider' => $provider,
                'event_id' => $eventId,
                'event_type' => $eventType,
                'created_at' => now(),
                'updated_at' => now(),
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

    private function handlePaymentPaid(array $payment): void
    {
        $paymentId = $payment['id'] ?? null;
        $status    = $payment['status'] ?? null;
        $metadata  = $payment['metadata'] ?? [];
        $tenantId  = $metadata['tenant_id'] ?? null;
        $planId    = $metadata['plan_id'] ?? null;
        $amount    = $payment['amount'] ?? 0;  // in halalas

        if (!$paymentId || $status !== 'paid' || !$tenantId || !$planId) {
            Log::warning('Moyasar webhook: missing required fields', compact('paymentId', 'tenantId', 'planId'));
            return;
        }

        // Re-verify via API (don't trust webhook data alone).
        $verified = $this->moyasarService->verifyPayment($paymentId);

        if (($verified['status'] ?? '') !== 'paid') {
            Log::warning("Moyasar webhook: payment {$paymentId} not paid per API");
            return;
        }

        $this->moyasarService->activateSubscription($tenantId, (int) $planId, (int) $amount, $paymentId);
    }
}
