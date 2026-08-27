<?php

declare(strict_types=1);

namespace App\Infrastructure\Pricing;

use App\Domain\Pricing\Contracts\CountryPriceSelector;
use App\Models\CountryPricing;
use App\Services\PricingService;

final class LegacyCountryPriceSelector implements CountryPriceSelector
{
    public function __construct(
        private readonly PricingService $pricing,
    ) {}

    public function setCountryOverride(string $countryCode): CountryPricing
    {
        return $this->pricing->setCountryOverride($countryCode);
    }
}
