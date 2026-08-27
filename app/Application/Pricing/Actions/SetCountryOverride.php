<?php

declare(strict_types=1);

namespace App\Application\Pricing\Actions;

use App\Models\CountryPricing;
use App\Services\PricingService;

final class SetCountryOverride
{
    public function __construct(
        private readonly PricingService $pricing,
    ) {}

    public function execute(string $countryCode): CountryPricing
    {
        return $this->pricing->setCountryOverride($countryCode);
    }
}
