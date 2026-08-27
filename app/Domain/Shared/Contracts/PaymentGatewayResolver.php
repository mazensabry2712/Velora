<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface PaymentGatewayResolver
{
    /** @return array<int, string> */
    public function forCountry(string $countryCode): array;

    public function isAvailable(string $gatewayKey, string $countryCode): bool;
}
