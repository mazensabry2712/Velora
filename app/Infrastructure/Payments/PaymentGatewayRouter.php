<?php

declare(strict_types=1);

namespace App\Infrastructure\Payments;

use App\Domain\Shared\Contracts\PaymentGatewayResolver;
use App\Models\CountryPricing;
use App\Models\SystemSetting;
use App\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\Cache;

final class PaymentGatewayRouter implements PaymentGatewayResolver
{
    public const GATEWAYS = [
        'stripe' => 'Stripe', 'paypal' => 'PayPal', 'mada' => 'Mada', 'fawry' => 'Fawry',
        'razorpay' => 'Razorpay', 'moyasar' => 'Moyasar', 'paymob' => 'Paymob', 'telr' => 'Telr',
        'tap' => 'Tap Payments', 'iyzico' => 'Iyzico', 'pagseguro' => 'PagSeguro',
    ];

    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    public function forCountry(string $countryCode): array
    {
        $countryCode = strtoupper(trim($countryCode));
        $version = (int) Cache::get('gateway_router:version', 1);
        $cacheKey = "gateway_router:v{$version}:{$countryCode}";

        return Cache::remember($cacheKey, 1800, function () use ($countryCode): array {
            $preferred = $this->getCountryPreferred($countryCode);
            $enabled = $this->getGloballyEnabled();

            $resolved = array_values(array_filter(
                $preferred,
                static fn (string $gateway): bool => in_array($gateway, $enabled, true),
            ));

            return $resolved ?: $enabled;
        });
    }

    /** @return array<int, array{key: string, label: string}> */
    public function forCountryWithLabels(string $countryCode): array
    {
        return array_map(
            static fn (string $key): array => [
                'key' => $key,
                'label' => self::GATEWAYS[$key] ?? ucfirst($key),
            ],
            $this->forCountry($countryCode),
        );
    }

    public function isAvailable(string $gatewayKey, string $countryCode): bool
    {
        return in_array(strtolower(trim($gatewayKey)), $this->forCountry($countryCode), true);
    }

    public function flushCache(string $countryCode): void
    {
        $version = (int) Cache::get('gateway_router:version', 1);
        Cache::forget("gateway_router:v{$version}:" . strtoupper($countryCode));
        Cache::forget('gateway_router:enabled');
    }

    /** @return array<int, string> */
    private function getCountryPreferred(string $countryCode): array
    {
        $pricing = CountryPricing::forCountry($countryCode);
        $supported = $this->gatewayManager->supported();

        return array_values(array_filter(
            array_map('strtolower', $pricing->payment_methods ?? []),
            static fn (string $gateway): bool => in_array($gateway, $supported, true),
        ));
    }

    /** @return array<int, string> */
    private function getGloballyEnabled(): array
    {
        return Cache::remember('gateway_router:enabled', 600, function (): array {
            $supported = $this->gatewayManager->supported();
            $enabled = [];

            foreach (array_keys(self::GATEWAYS) as $key) {
                if (in_array($key, $supported, true) && SystemSetting::get("{$key}_enabled", false)) {
                    $enabled[] = $key;
                }
            }

            return $enabled ?: (in_array('stripe', $supported, true) ? ['stripe'] : []);
        });
    }
}
