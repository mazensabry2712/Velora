<?php

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PaymobGateway — Egypt's largest payment processor.
 *
 * Paymob uses a 3-step API flow:
 *   1. Authenticate → get auth_token
 *   2. Create Order → get order_id
 *   3. Create Payment Key → get payment_key → redirect to iframe
 *
 * Credentials (set in config/services.php):
 *   services.paymob.api_key
 *   services.paymob.integration_id  (card payments integration)
 *   services.paymob.iframe_id
 */
class PaymobGateway implements PaymentGatewayInterface
{
    private string $apiKey;
    private string $integrationId;
    private string $iframeId;
    private string $baseUrl = 'https://accept.paymob.com/api';

    public function __construct()
    {
        $this->apiKey        = config('services.paymob.api_key', '');
        $this->integrationId = config('services.paymob.integration_id', '');
        $this->iframeId      = config('services.paymob.iframe_id', '');
    }

    /**
     * Run the Paymob 3-step authentication and return the hosted iFrame URL.
     *
     * Expects $data:
     *   amount         (float, in EGP)
     *   currency       (string, default EGP)
     *   customer_email (string)
     *   customer_name  (string)
     *   plan_id        (int)
     *   tenant_id      (string)
     */
    public function createCheckout(array $data): string
    {
        // Step 1: Authenticate
        $authToken = $this->getAuthToken();

        // Step 2: Create Order
        $amountPiasters = (int) round((float) ($data['amount'] ?? 0) * 100);
        $currency       = strtoupper($data['currency'] ?? 'EGP');

        $orderResponse = Http::post("{$this->baseUrl}/ecommerce/orders", [
            'auth_token'     => $authToken,
            'delivery_needed' => false,
            'amount_cents'   => $amountPiasters,
            'currency'       => $currency,
            'items'          => [],
        ])->throw()->json();

        $orderId = $orderResponse['id'];

        // Step 3: Create Payment Key
        [$firstName, $lastName] = $this->splitName($data['customer_name'] ?? '');

        $keyResponse = Http::post("{$this->baseUrl}/acceptance/payment_keys", [
            'auth_token'     => $authToken,
            'amount_cents'   => $amountPiasters,
            'expiration'     => 3600,
            'order_id'       => $orderId,
            'billing_data'   => [
                'email'        => $data['customer_email'] ?? 'n/a@velora.com',
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'phone_number' => 'N/A',
                'apartment'    => 'N/A',
                'floor'        => 'N/A',
                'street'       => 'N/A',
                'building'     => 'N/A',
                'city'         => 'N/A',
                'country'      => 'EG',
                'state'        => 'N/A',
            ],
            'currency'        => $currency,
            'integration_id'  => (int) $this->integrationId,
        ])->throw()->json();

        $paymentKey = $keyResponse['token'];

        return "https://accept.paymob.com/api/acceptance/iframes/{$this->iframeId}?payment_token={$paymentKey}";
    }

    /**
     * Verify an incoming Paymob callback / HMAC notification.
     *
     * $payload must contain 'hmac' and the full transaction data.
     */
    public function verifyPayment(array $payload): bool
    {
        $hmac = $payload['hmac'] ?? null;

        if (!$hmac) {
            return false;
        }

        $hmacSecret = config('services.paymob.hmac_secret', '');
        $computed   = $this->computeHmac($payload, $hmacSecret);

        if (!hash_equals($computed, $hmac)) {
            Log::warning('PaymobGateway: HMAC mismatch', ['received' => $hmac]);
            return false;
        }

        return ($payload['success'] ?? 'false') === 'true';
    }

    /**
     * Paymob supports refunds via their API.
     * $transactionId is Paymob's numeric transaction ID.
     */
    public function refund(string $transactionId): bool
    {
        try {
            $authToken = $this->getAuthToken();

            Http::post("{$this->baseUrl}/acceptance/void_refund/refund", [
                'auth_token'     => $authToken,
                'transaction_id' => $transactionId,
                'amount_cents'   => 0, // 0 = full refund in Paymob
            ])->throw();

            return true;
        } catch (\Exception $e) {
            Log::error('PaymobGateway::refund failed: ' . $e->getMessage(), ['transaction_id' => $transactionId]);
            return false;
        }
    }

    // ─── Private helpers ────────────────────────────────────────────────────

    private function getAuthToken(): string
    {
        $response = Http::post("{$this->baseUrl}/auth/tokens", [
            'api_key' => $this->apiKey,
        ])->throw()->json();

        return $response['token'];
    }

    private function computeHmac(array $data, string $secret): string
    {
        // Paymob HMAC fields (order matters — see Paymob docs)
        $fields = [
            'amount_cents', 'created_at', 'currency', 'error_occured',
            'has_parent_transaction', 'id', 'integration_id', 'is_3d_secure',
            'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
            'is_voided', 'order', 'owner', 'pending', 'source_data_pan',
            'source_data_sub_type', 'source_data_type', 'success',
        ];

        $concatenated = implode('', array_map(fn ($f) => (string) ($data[$f] ?? ''), $fields));
        return hash_hmac('sha512', $concatenated, $secret);
    }

    private function splitName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);
        return [$parts[0] ?? 'N/A', $parts[1] ?? 'N/A'];
    }
}
