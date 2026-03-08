<?php

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayPalGateway — Global card & wallet payments.
 *
 * Uses PayPal Orders API v2 (no SDK required).
 * Credentials (set in config/services.php):
 *   services.paypal.client_id
 *   services.paypal.client_secret
 *   services.paypal.mode  (sandbox | live)
 */
class PayPalGateway implements PaymentGatewayInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId     = config('services.paypal.client_id', '');
        $this->clientSecret = config('services.paypal.client_secret', '');
        $mode               = config('services.paypal.mode', 'live');
        $this->baseUrl      = $mode === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    /**
     * Create a PayPal Order and return the "payer-action" approval URL.
     *
     * Expects $data:
     *   amount       (float, major currency units)
     *   currency     (string, ISO-4217, e.g. USD)
     *   success_url  (string)
     *   cancel_url   (string)
     *   plan_id      (int)
     *   tenant_id    (string)
     */
    public function createCheckout(array $data): string
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => strtoupper($data['currency'] ?? 'USD'),
                        'value'         => number_format((float) ($data['amount'] ?? 0), 2, '.', ''),
                    ],
                    'custom_id' => json_encode([
                        'tenant_id' => $data['tenant_id'] ?? null,
                        'plan_id'   => $data['plan_id'] ?? null,
                    ]),
                ]],
                'application_context' => [
                    'return_url' => $data['success_url'] ?? url('/billing/success'),
                    'cancel_url' => $data['cancel_url'] ?? url('/billing/expired'),
                    'brand_name' => config('app.name', 'Velora'),
                    'user_action' => 'PAY_NOW',
                ],
            ])->throw()->json();

        $approvalLink = collect($response['links'])->firstWhere('rel', 'payer-action')
            ?? collect($response['links'])->firstWhere('rel', 'approve');

        return $approvalLink['href']
            ?? throw new \RuntimeException('PayPal did not return an approval URL.');
    }

    /**
     * Capture (verify) a PayPal Order after the payer approves.
     *
     * $payload must contain 'order_id'.
     */
    public function verifyPayment(array $payload): bool
    {
        $orderId = $payload['order_id']
            ?? throw new \InvalidArgumentException('order_id is required to verify a PayPal payment.');

        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture")
                ->throw()
                ->json();

            return ($response['status'] ?? '') === 'COMPLETED';
        } catch (\Exception $e) {
            Log::error('PayPalGateway::verifyPayment failed: ' . $e->getMessage(), $payload);
            return false;
        }
    }

    /**
     * Issue a full refund for a PayPal Capture ID.
     *
     * $transactionId is the PayPal Capture ID (not Order ID).
     */
    public function refund(string $transactionId): bool
    {
        try {
            $accessToken = $this->getAccessToken();

            Http::withToken($accessToken)
                ->post("{$this->baseUrl}/v2/payments/captures/{$transactionId}/refund", [])
                ->throw();

            return true;
        } catch (\Exception $e) {
            Log::error('PayPalGateway::refund failed: ' . $e->getMessage(), ['transaction_id' => $transactionId]);
            return false;
        }
    }

    // ─── Private helpers ────────────────────────────────────────────────────

    private function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials'])
            ->throw()
            ->json();

        return $response['access_token'];
    }
}
