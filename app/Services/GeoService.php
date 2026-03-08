<?php

namespace App\Services;

use App\Models\CountrySetting;
use App\Models\CountryTax;
use App\Models\CountryPricing;
use App\Models\PlanPrice;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GeoService
{
    /** Ordered list of supported locales */
    const SUPPORTED_LOCALES = [
        'en','ar','fr','es','de','it','pt','ru','zh','ja','tr','hi','ko','nl','id',
    ];

    // ─── Country Detection ────────────────────────────────────────────────────

    /**
     * Detect the two-letter country code from the request.
     * Priority: Cloudflare CF-IPCountry → X-Country-Code header → 'US' fallback.
     */
    public function getCountryCode(Request $request): string
    {
        // 1. Cloudflare header (free CDN)
        if ($cf = $request->header('CF-IPCountry')) {
            $cf = strtoupper(trim($cf));
            if (strlen($cf) === 2 && $cf !== 'XX') {
                return $cf;
            }
        }

        // 2. Manual/staging override header
        if ($hdr = $request->header('X-Country-Code')) {
            return strtoupper(trim($hdr));
        }

        return 'US';
    }

    // ─── Locale Resolution ────────────────────────────────────────────────────

    /**
     * Resolve the best locale for a country code.
     */
    public function getLocaleForCountry(string $code): string
    {
        $setting = CountrySetting::getByCode($code);
        $locale  = $setting?->default_language ?? SystemSetting::get('default_language', 'en');

        return $this->sanitiseLocale((string) $locale);
    }

    private function sanitiseLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));
        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'en';
    }

    // ─── Currency Resolution ──────────────────────────────────────────────────

    /**
     * Resolve the ISO-4217 currency code for a country.
     */
    public function getCurrencyForCountry(string $code): string
    {
        $setting  = CountrySetting::getByCode($code);
        $currency = $setting?->default_currency ?? SystemSetting::get('default_currency', 'USD');
        return strtoupper(trim((string) $currency));
    }

    // ─── Plan Pricing ─────────────────────────────────────────────────────────

    /**
     * Get the best available PlanPrice for a plan and country.
     * Falls back to the default price if no country-specific price exists.
     */
    public function getPlanPrice(SubscriptionPlan $plan, string $countryCode): ?PlanPrice
    {
        return PlanPrice::forPlanAndCountry($plan->id, $countryCode);
    }

    /**
     * Get all active plans with their localised price objects for a country.
     * Returns a Collection of SubscriptionPlan models, each with a `geo_price` attribute.
     */
    public function getPlansForCountry(string $countryCode): \Illuminate\Support\Collection
    {
        $plans = Cache::remember("plans_for_country:{$countryCode}", 1800, function () use ($countryCode) {
            return SubscriptionPlan::where('is_active', true)
                ->orderBy('price')
                ->get()
                ->map(function (SubscriptionPlan $plan) use ($countryCode) {
                    $plan->geo_price = PlanPrice::forPlanAndCountry($plan->id, $countryCode);
                    return $plan;
                });
        });

        return $plans;
    }

    // ─── Tax Calculation ──────────────────────────────────────────────────────

    /**
     * Get tax percentage (0–100) for a country. Returns 0 if no tax or disabled.
     */
    public function getTaxPercentage(string $countryCode): float
    {
        if (! SystemSetting::get('enable_vat_per_country', false)) {
            return 0.0;
        }
        return CountryTax::percentageFor($countryCode);
    }

    /**
     * Calculate the tax amount for a given base amount and country.
     */
    public function calculateTax(float $amount, string $countryCode): float
    {
        $percentage = $this->getTaxPercentage($countryCode);
        if ($percentage <= 0) {
            return 0.0;
        }
        return round($amount * ($percentage / 100), 2);
    }

    /**
     * Return amount + tax.
     */
    public function amountWithTax(float $amount, string $countryCode): float
    {
        return round($amount + $this->calculateTax($amount, $countryCode), 2);
    }

    // ─── Full Multi-Region Context ────────────────────────────────────────────

    /**
     * Return the complete pricing + region context for a country in one call.
     *
     * This is the single source of truth used by the checkout, pricing page,
     * and any other consumer that needs to know: "what does the user pay, in
     * what currency, with which gateways, and including what taxes?"
     *
     * Returns:
     *   country_code, country_name, locale, currency,
     *   base_price, tax_pct, tax_name, tax_amount, total_price,
     *   formatted_price, payment_methods (gateway keys)
     */
    public function getPricingContext(string $countryCode): array
    {
        $countryCode = strtoupper(trim($countryCode));

        return Cache::remember("geo_pricing_context:{$countryCode}", 900, function () use ($countryCode) {
            $pricing    = CountryPricing::forCountry($countryCode);
            $taxRecord  = CountryTax::forCountry($countryCode);
            $locale     = $this->getLocaleForCountry($countryCode);
            $basePrice  = (float) $pricing->price;
            $taxPct     = SystemSetting::get('enable_vat_per_country', false)
                ? (float) ($taxRecord?->tax_percentage ?? 0)
                : 0.0;
            $taxAmount  = $taxPct > 0 ? round($basePrice * ($taxPct / 100), 2) : 0.0;
            $total      = round($basePrice + $taxAmount, 2);

            return [
                'country_code'    => $pricing->country_code,
                'country_name'    => $pricing->country_name,
                'locale'          => $locale,
                'currency'        => $pricing->currency,
                'base_price'      => $basePrice,
                'tax_pct'         => $taxPct,
                'tax_name'        => $taxRecord?->tax_name ?? 'VAT',
                'tax_amount'      => $taxAmount,
                'total_price'     => $total,
                'formatted_price' => $pricing->formattedPrice(),
                'payment_methods' => $pricing->payment_methods ?? [],
                'is_global'       => $pricing->country_code === 'GLOBAL',
            ];
        });
    }
}
