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
use Illuminate\Support\Facades\Schema;

class GeoService
{
    public function getCountryCode(Request $request): string
    {
        if ($cf = $request->header('CF-IPCountry')) {
            $cf = strtoupper(trim($cf));
            if (strlen($cf) === 2 && $cf !== 'XX') {
                return $cf;
            }
        }

        if ($hdr = $request->header('X-Country-Code')) {
            $country = strtoupper(trim($hdr));
            if (preg_match('/^[A-Z]{2}$/', $country)) {
                return $country;
            }
        }

        return 'US';
    }

    public function getLocaleForCountry(string $code): string
    {
        $setting = CountrySetting::getByCode($code);
        $locale = $setting?->default_language ?? SystemSetting::get('default_language', config('app.fallback_locale', 'en'));

        return $this->sanitiseLocale((string) $locale);
    }

    private function sanitiseLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));
        $supported = config('localizer.supported_locales', []);

        return in_array($locale, $supported, true)
            ? $locale
            : (string) config('app.fallback_locale', 'en');
    }

    public function getCurrencyForCountry(string $code): string
    {
        $setting = CountrySetting::getByCode($code);
        $currency = $setting?->default_currency ?? SystemSetting::get('default_currency', 'USD');

        return strtoupper(trim((string) $currency));
    }

    public function getPlanPrice(SubscriptionPlan $plan, string $countryCode): ?PlanPrice
    {
        return PlanPrice::forPlanAndCountry($plan->id, $countryCode);
    }

    public function getPlansForCountry(string $countryCode): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('subscription_plans')) {
            return collect();
        }

        return Cache::remember("plans_for_country:{$countryCode}", 1800, function () use ($countryCode) {
            return SubscriptionPlan::where('is_active', true)
                ->orderBy('price')
                ->get()
                ->map(function (SubscriptionPlan $plan) use ($countryCode) {
                    $plan->geo_price = PlanPrice::forPlanAndCountry($plan->id, $countryCode);

                    return $plan;
                });
        });
    }

    public function getTaxPercentage(string $countryCode): float
    {
        if (! SystemSetting::get('enable_vat_per_country', false)) {
            return 0.0;
        }

        return CountryTax::percentageFor($countryCode);
    }

    public function calculateTax(float $amount, string $countryCode): float
    {
        $percentage = $this->getTaxPercentage($countryCode);
        if ($percentage <= 0) {
            return 0.0;
        }

        return round($amount * ($percentage / 100), 2);
    }

    public function amountWithTax(float $amount, string $countryCode): float
    {
        return round($amount + $this->calculateTax($amount, $countryCode), 2);
    }

    public function getPricingContext(string $countryCode): array
    {
        $countryCode = strtoupper(trim($countryCode));

        return Cache::remember("geo_pricing_context:{$countryCode}", 900, function () use ($countryCode) {
            $pricing = CountryPricing::forCountry($countryCode);
            $taxRecord = CountryTax::forCountry($countryCode);
            $locale = $this->getLocaleForCountry($countryCode);
            $basePrice = (float) $pricing->price;
            $taxPct = SystemSetting::get('enable_vat_per_country', false)
                ? (float) ($taxRecord?->tax_percentage ?? 0)
                : 0.0;
            $taxAmount = $taxPct > 0 ? round($basePrice * ($taxPct / 100), 2) : 0.0;
            $total = round($basePrice + $taxAmount, 2);

            return [
                'country_code' => $pricing->country_code,
                'country_name' => $pricing->country_name,
                'locale' => $locale,
                'currency' => $pricing->currency,
                'base_price' => $basePrice,
                'tax_pct' => $taxPct,
                'tax_name' => $taxRecord?->tax_name ?? 'VAT',
                'tax_amount' => $taxAmount,
                'total_price' => $total,
                'formatted_price' => $pricing->formattedPrice(),
                'payment_methods' => $pricing->payment_methods ?? [],
                'is_global' => $pricing->country_code === 'GLOBAL',
            ];
        });
    }
}
