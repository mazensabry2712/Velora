<?php

namespace App\Services;

use App\Domain\Pricing\Contracts\CountryPriceSelector;
use App\Models\CountryPricing;
use Illuminate\Http\Request;

class PricingService implements CountryPriceSelector
{
    /**
     * Resolve the CountryPricing record for the current request.
     * Priority: cookie override → session override → geo-detected country → GLOBAL.
     */
    public function getPricingForRequest(Request $request): CountryPricing
    {
        $cookieOverride = $request->cookie('velora_country_override');
        if ($cookieOverride && preg_match('/^[A-Z]{2,10}$/', strtoupper($cookieOverride))) {
            return $this->getPricingForCountry(strtoupper($cookieOverride));
        }

        $override = session('pricing_country_override');
        if ($override) {
            return $this->getPricingForCountry($override);
        }

        $detected = session('detected_country', 'US');

        return $this->getPricingForCountry($detected);
    }

    /**
     * Get pricing for an explicit country code. Falls back to GLOBAL.
     */
    public function getPricingForCountry(string $countryCode): CountryPricing
    {
        return CountryPricing::forCountry($countryCode);
    }

    /**
     * Set a manual country override in the session and as a permanent cookie.
     * Returns the new pricing record.
     */
    public function setCountryOverride(string $countryCode): CountryPricing
    {
        $code = strtoupper($countryCode);
        session(['pricing_country_override' => $code]);
        cookie()->queue(cookie()->forever('velora_country_override', $code));

        return $this->getPricingForCountry($code);
    }

    /**
     * Clear the manual country override.
     */
    public function clearCountryOverride(): void
    {
        session()->forget('pricing_country_override');
    }

    /**
     * Return an array summary suitable for views.
     */
    public function getPricingSummary(Request $request): array
    {
        $pricing = $this->getPricingForRequest($request);

        return [
            'country_code'    => $pricing->country_code,
            'country_name'    => $pricing->country_name,
            'price'           => (float) $pricing->price,
            'currency'        => $pricing->currency,
            'formatted_price' => $pricing->formattedPrice(),
            'payment_methods' => $pricing->payment_methods ?? [],
            'is_global'       => $pricing->country_code === 'GLOBAL',
        ];
    }

    /**
     * All active country pricing entries (for dropdowns / admin).
     */
    public function allActive()
    {
        return CountryPricing::active()
            ->orderByRaw("CASE WHEN country_code = 'GLOBAL' THEN 1 ELSE 0 END")
            ->orderBy('country_name')
            ->get();
    }
}
