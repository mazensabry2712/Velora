<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing;

use App\Domain\Billing\Contracts\CheckoutSessionCreator;
use App\Payments\PaymentGatewayManager;

final class PaymentGatewayCheckoutSessionCreator implements CheckoutSessionCreator
{
    public function __construct(
        private readonly PaymentGatewayManager $paymentManager,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, string $gateway): string
    {
        return $this->paymentManager
            ->driver($gateway)
            ->createCheckout($data);
    }
}
