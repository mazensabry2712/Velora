<?php

declare(strict_types=1);

namespace App\Application\Billing\Actions;

use App\Domain\Billing\Contracts\CheckoutSessionCreator;
use App\Application\Billing\DTOs\CheckoutData;

final class CreateCheckoutSession
{
    public function __construct(
        private readonly CheckoutSessionCreator $checkout,
    ) {}

    /** @param array<string, mixed> $metadata */
    public function execute(CheckoutData $data, string $gateway, array $metadata = []): string
    {
        return $this->checkout->create([
            'plan_id' => $data->planId,
            'tenant_id' => $data->tenantId,
            'customer_email' => $data->customerEmail,
            'customer_name' => $data->customerName,
            'success_url' => 'https://' . $data->tenantDomain . '/billing/success',
            'cancel_url' => 'https://' . $data->tenantDomain . '/billing/expired',
            'amount' => $data->baseAmount,
            'currency' => $data->currency,
            'country_code' => $data->countryCode,
            'stripe_price_id' => $data->stripePriceId,
            'metadata' => array_merge([
                'plan_name' => null,
                'tax_amount' => $data->taxAmount,
                'total_amount' => $data->totalAmount,
            ], $metadata),
        ], $gateway);
    }
}
