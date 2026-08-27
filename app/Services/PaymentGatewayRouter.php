<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Shared\Contracts\PaymentGatewayResolver;
use App\Infrastructure\Payments\PaymentGatewayRouter as InfrastructurePaymentGatewayRouter;

/**
 * @deprecated Use the PaymentGatewayResolver contract. This compatibility
 * adapter is intentionally kept source-compatible while callers migrate.
 */
final class PaymentGatewayRouter implements PaymentGatewayResolver
{
    public function forCountry(string $countryCode): array
    {
        return app(InfrastructurePaymentGatewayRouter::class)->forCountry($countryCode);
    }

    public function forCountryWithLabels(string $countryCode): array
    {
        return app(InfrastructurePaymentGatewayRouter::class)->forCountryWithLabels($countryCode);
    }

    public function isAvailable(string $gatewayKey, string $countryCode): bool
    {
        return app(InfrastructurePaymentGatewayRouter::class)->isAvailable($gatewayKey, $countryCode);
    }

    public function flushCache(string $countryCode): void
    {
        app(InfrastructurePaymentGatewayRouter::class)->flushCache($countryCode);
    }
}
