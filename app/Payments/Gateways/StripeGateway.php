<?php

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGatewayInterface;
use App\Services\GeoService;
use App\Services\StripeService;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected StripeService $stripeService,
        protected GeoService    $geoService,
    ) {}

    /**
     * Create a Stripe Checkout Session and return the hosted URL.
     *
     * Expects $data:
     *   plan_id, tenant_id, customer_email, customer_name,
     *   success_url, cancel_url, currency, amount, metadata[]
     *   stripe_price_id (resolved upstream)
     */
    public function createCheckout(array $data): string
    {
        $stripePriceId = $data['stripe_price_id']
            ?? throw new \InvalidArgumentException('stripe_price_id is required for StripeGateway.');

        $session = $this->stripeService->createCheckoutSession(
            tenantId:       $data['tenant_id'],
            stripePriceId:  $stripePriceId,
            customerEmail:  $data['customer_email'],
            customerName:   $data['customer_name'],
            successUrl:     $data['success_url'],
            cancelUrl:      $data['cancel_url'],
            metadata:       array_merge($data['metadata'] ?? [], [
                'plan_id'      => $data['plan_id'] ?? null,
                'country_code' => $data['country_code'] ?? null,
                'currency'     => $data['currency'] ?? 'USD',
                'base_amount'  => $data['amount'] ?? 0,
            ]),
        );

        return $session->url;
    }

    /**
     * Verify the payment status of a completed Stripe Checkout Session.
     *
     * $payload must contain 'session_id'.
     */
    public function verifyPayment(array $payload): bool
    {
        $sessionId = $payload['session_id']
            ?? throw new \InvalidArgumentException('session_id is required to verify a Stripe payment.');

        try {
            $session = $this->stripeService->retrieveCheckoutSession($sessionId);
            return $session->payment_status === 'paid';
        } catch (\Exception $e) {
            Log::error('StripeGateway::verifyPayment failed: ' . $e->getMessage(), $payload);
            return false;
        }
    }

    /**
     * Issue a full refund via Stripe Refunds API.
     *
     * $transactionId is the Stripe PaymentIntent ID (pi_xxx).
     */
    public function refund(string $transactionId): bool
    {
        try {
            $this->stripeService->refund($transactionId);
            return true;
        } catch (\Exception $e) {
            Log::error('StripeGateway::refund failed: ' . $e->getMessage(), ['transaction_id' => $transactionId]);
            return false;
        }
    }
}
