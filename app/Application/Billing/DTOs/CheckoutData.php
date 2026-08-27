<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

final readonly class CheckoutData
{
    public function __construct(
        public int $tenantId,
        public int $planId,
        public string $customerName,
        public string $customerEmail,
        public string $countryCode,
        public string $currency,
        public float $baseAmount,
        public float $taxAmount,
        public float $totalAmount,
        public ?string $stripePriceId,
        public ?string $tenantDomain,
    ) {}
}
