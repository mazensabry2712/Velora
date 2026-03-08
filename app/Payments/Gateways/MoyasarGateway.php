<?php

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGatewayInterface;
use App\Services\MoyasarService;
use Illuminate\Support\Facades\Log;

class MoyasarGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected MoyasarService $moyasarService,
    ) {}

    /**
     * Store the Moyasar checkout context in session and return
     * the local page URL where the Moyasar JS widget is rendered.
     *
     * Expects $data:
     *   plan_id, amount (in SAR, will be converted to halalas),
     *   currency (default SAR)
     */
    public function createCheckout(array $data): string
    {
        $planId        = $data['plan_id']
            ?? throw new \InvalidArgumentException('plan_id is required for MoyasarGateway.');
        $amountMajor   = (float) ($data['amount'] ?? 0);
        $amountHalalas = (int) round($amountMajor * 100);
        $currency      = strtoupper($data['currency'] ?? 'SAR');

        session([
            'moyasar_plan_id'   => $planId,
            'moyasar_amount'    => $amountHalalas,
            'moyasar_plan_name' => $data['plan_name'] ?? '',
            'moyasar_currency'  => $currency,
        ]);

        return route('billing.moyasar.pay');
    }

    /**
     * Verify a Moyasar payment via their API.
     *
     * $payload must contain 'payment_id'.
     */
    public function verifyPayment(array $payload): bool
    {
        $paymentId = $payload['payment_id']
            ?? throw new \InvalidArgumentException('payment_id is required to verify a Moyasar payment.');

        try {
            $payment = $this->moyasarService->verifyPayment($paymentId);
            return ($payment['status'] ?? '') === 'paid';
        } catch (\Exception $e) {
            Log::error('MoyasarGateway::verifyPayment failed: ' . $e->getMessage(), $payload);
            return false;
        }
    }

    /**
     * Moyasar refunds are handled manually via the dashboard.
     * Returning false signals the caller to fall back to manual flow.
     */
    public function refund(string $transactionId): bool
    {
        Log::info('MoyasarGateway::refund: automatic refunds not supported. Manual refund required.', [
            'transaction_id' => $transactionId,
        ]);
        return false;
    }

    public function getPublishableKey(): string
    {
        return $this->moyasarService->getPublishableKey();
    }
}
