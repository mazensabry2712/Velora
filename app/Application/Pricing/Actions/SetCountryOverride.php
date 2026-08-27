<?php

declare(strict_types=1);

namespace App\Application\Pricing\Actions;

use App\Domain\Pricing\Contracts\CountryPriceSelector;
use App\Models\CountryPricing;

final class SetCountryOverride
{
    public function __construct(
        private readonly CountryPriceSelector $pricing,
    ) {}

    public function execute(string $countryCode): CountryPricing
    {
        return $this->pricing->setCountryOverride($countryCode);
    }
}
