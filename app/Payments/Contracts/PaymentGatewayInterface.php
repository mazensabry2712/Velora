<?php

namespace App\Payments\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Initiate a checkout and return the redirect URL.
     *
     * Expected $data keys:
     *   - plan_id       (int)    Subscription plan ID
     *   - tenant_id     (string) Tenant UUID
     *   - customer_email (string)
     *   - customer_name  (string)
     *   - success_url   (string)
     *   - cancel_url    (string)
     *   - amount        (float)  Total amount in major currency units
     *   - currency      (string) ISO-4217 (USD, SAR, EGP…)
     *   - metadata      (array)  Extra key-value data to attach to the session
     */
    public function createCheckout(array $data): string;

    /**
     * Verify a payment given raw gateway $payload / IDs.
     * Returns true when the payment is confirmed as paid.
     */
    public function verifyPayment(array $payload): bool;

    /**
     * Issue a full refund for the given gateway transaction ID.
     * Returns true on success.
     */
    public function refund(string $transactionId): bool;
}
