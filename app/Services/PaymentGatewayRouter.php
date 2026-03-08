<?php

namespace App\Services;

use App\Models\CountryPricing;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

/**
 * PaymentGatewayRouter
 *
 * Resolves which payment gateways are available for a given country.
 * Combines the country's preferred gateways (from country_pricing) with
 * the globally-enabled gateways (from system_settings), then filters to
 * only return gateways that are actually enabled in system settings.
 *
 * This is the 4th layer of the Multi-Region architecture: Dynamic Gateway Routing.
 *
 * Usage:
 *   $gateways = app(PaymentGatewayRouter::class)->forCountry('EG');
 *   // → ['paymob', 'fawry', 'stripe']
 */
class PaymentGatewayRouter
{
    /**
     * All supported gateway keys and their human-readable labels.
     */
    public const GATEWAYS = [
        'stripe'     => 'Stripe',
        'paypal'     => 'PayPal',
        'mada'       => 'Mada',
        'fawry'      => 'Fawry',
        'razorpay'   => 'Razorpay',
        'moyasar'    => 'Moyasar',
        'paymob'     => 'Paymob',
        'telr'       => 'Telr',
        'tap'        => 'Tap Payments',
        'iyzico'     => 'Iyzico',
        'pagseguro'  => 'PagSeguro',
    ];

    /**
     * Returns the ordered list of enabled gateways for a country.
     *
     * Logic:
     * 1. Load gateways preferred for this country (from country_pricing).
     * 2. Filter to only those enabled in system_settings (e.g. stripe_enabled = 1).
     * 3. If nothing matches, fall back to globally enabled gateways.
     *
     * @param  string $countryCode  ISO-3166-1 alpha-2 (e.g. "EG", "SA", "US")
     * @return array<string>        Ordered list of gateway keys (e.g. ['stripe','fawry'])
     */
    public function forCountry(string $countryCode): array
    {
        $countryCode = strtoupper(trim($countryCode));

        return Cache::remember("gateway_router:{$countryCode}", 1800, function () use ($countryCode) {
            $preferred  = $this->getCountryPreferred($countryCode);
            $enabled    = $this->getGloballyEnabled();

            // Intersect: keep preferred order but only enabled gateways
            $resolved = array_values(array_filter($preferred, fn ($g) => in_array($g, $enabled, true)));

            // Fallback: if no preferred gateways are enabled, return all enabled gateways
            return $resolved ?: $enabled;
        });
    }

    /**
     * Returns labelled gateway metadata for a country (suitable for views / APIs).
     *
     * @return array<array{key: string, label: string}>
     */
    public function forCountryWithLabels(string $countryCode): array
    {
        return array_map(
            fn ($key) => ['key' => $key, 'label' => self::GATEWAYS[$key] ?? ucfirst($key)],
            $this->forCountry($countryCode)
        );
    }

    /**
     * Check if a specific gateway is available for a country.
     */
    public function isAvailable(string $gatewayKey, string $countryCode): bool
    {
        return in_array(strtolower($gatewayKey), $this->forCountry($countryCode), true);
    }

    /**
     * Flush the cached resolution for a country (e.g. when settings change).
     */
    public function flushCache(string $countryCode): void
    {
        Cache::forget('gateway_router:' . strtoupper($countryCode));
        Cache::forget('gateway_router:enabled');
    }

    // ─── Private helpers ────────────────────────────────────────────────────

    /**
     * Load the preferred gateways for a country from country_pricing.
     * Falls back to GLOBAL if no specific entry exists.
     */
    private function getCountryPreferred(string $countryCode): array
    {
        $pricing = CountryPricing::forCountry($countryCode);
        return array_map('strtolower', $pricing->payment_methods ?? []);
    }

    /**
     * Return all gateways that have `{key}_enabled = 1` in system_settings.
     * Cached for 10 minutes (settings change infrequently).
     */
    private function getGloballyEnabled(): array
    {
        return Cache::remember('gateway_router:enabled', 600, function () {
            $enabled = [];
            foreach (array_keys(self::GATEWAYS) as $key) {
                if (SystemSetting::get("{$key}_enabled", false)) {
                    $enabled[] = $key;
                }
            }
            // Always include 'stripe' as ultimate fallback if enabled
            return $enabled ?: ['stripe'];
        });
    }
}
