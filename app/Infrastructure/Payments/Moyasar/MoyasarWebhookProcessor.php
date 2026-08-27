<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments\Moyasar;

use App\Services\MoyasarService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class MoyasarWebhookProcessor implements \App\Domain\Billing\Contracts\MoyasarWebhookProcessor
{
    public function __construct(private readonly MoyasarService $moyasarService) {}

    public function process(string $payload, ?string $signature): array
    {
        $secret = config('services.moyasar.webhook_secret');
        if ($secret) {
            $expected = hash_hmac('sha256', $payload, $secret);
            if (! hash_equals($expected, (string) $signature)) {
                throw new \InvalidArgumentException('Invalid signature');
            }
        }

        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            throw new \InvalidArgumentException('Invalid payload');
        }

        $type = $decoded['type'] ?? null;
        $data = $decoded['data'] ?? [];
        $eventId = (string) ($decoded['id'] ?? $data['id'] ?? hash('sha256', $payload));

        Log::info('Moyasar webhook received', [
            'type' => $type,
            'event_id' => $eventId,
            'payment_id' => $data['id'] ?? null,
        ]);

        if ($this->isDuplicate($eventId, $type)) {
            return ['status' => 'ok', 'duplicate' => true];
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
            throw $e;
        }

        return ['status' => 'ok'];
    }

    private function isDuplicate(string $eventId, ?string $eventType): bool
    {
        try {
            DB::table('webhook_events')->insert([
                'provider' => 'moyasar',
                'event_id' => $eventId,
                'event_type' => $eventType,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return false;
        } catch (QueryException $e) {
            if (str_contains(strtolower($e->getMessage()), 'unique')) {
                return true;
            }
            throw $e;
        }
    }

    private function handlePaymentPaid(array $payment): void
    {
        $paymentId = $payment['id'] ?? null;
        $status = $payment['status'] ?? null;
        $metadata = $payment['metadata'] ?? [];
        $tenantId = $metadata['tenant_id'] ?? null;
        $planId = $metadata['plan_id'] ?? null;
        $amount = $payment['amount'] ?? 0;

        if (! $paymentId || $status !== 'paid' || ! $tenantId || ! $planId) {
            Log::warning('Moyasar webhook: missing required fields', compact('paymentId', 'tenantId', 'planId'));
            return;
        }

        $verified = $this->moyasarService->verifyPayment($paymentId);
        if (($verified['status'] ?? '') !== 'paid') {
            Log::warning("Moyasar webhook: payment {$paymentId} not paid per API");
            return;
        }

        $this->moyasarService->activateSubscription($tenantId, (int) $planId, (int) $amount, $paymentId);
    }
}
