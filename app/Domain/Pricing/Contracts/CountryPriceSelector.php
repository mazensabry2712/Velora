<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Contracts;

use App\Models\CountryPricing;

interface CountryPriceSelector
{
    public function setCountryOverride(string $countryCode): CountryPricing;
}
