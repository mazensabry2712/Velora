<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\Gateways\MoyasarGateway;
use App\Payments\Gateways\PaymobGateway;
use App\Payments\Gateways\PayPalGateway;
use App\Payments\Gateways\StripeGateway;
use Illuminate\Contracts\Container\Container;

/**
 * PaymentGatewayManager
 *
 * Resolves the correct PaymentGatewayInterface implementation
 * by gateway key. New gateways only need to be added here and
 * in the Gateways/ directory — no other code changes required.
 *
 * Usage:
 *   $url = app(PaymentGatewayManager::class)
 *              ->driver('stripe')
 *              ->createCheckout($data);
 */
class PaymentGatewayManager
{
    /**
     * Map of gateway key → concrete class.
     * Add new gateways here as they are implemented.
     */
    private const DRIVERS = [
        'stripe'  => StripeGateway::class,
        'moyasar' => MoyasarGateway::class,
        'paymob'  => PaymobGateway::class,
        'paypal'  => PayPalGateway::class,
    ];

    public function __construct(
        protected Container $app,
    ) {}

    /**
     * Resolve a gateway driver by its key.
     *
     * @throws \InvalidArgumentException When the gateway is not registered.
     */
    public function driver(string $gateway): PaymentGatewayInterface
    {
        $key   = strtolower(trim($gateway));
        $class = self::DRIVERS[$key]
            ?? throw new \InvalidArgumentException("Payment gateway [{$key}] is not supported.");

        return $this->app->make($class);
    }

    /**
     * Return the list of all registered gateway keys.
     *
     * @return array<string>
     */
    public function supported(): array
    {
        return array_keys(self::DRIVERS);
    }

    /**
     * Check whether a gateway key is registered.
     */
    public function has(string $gateway): bool
    {
        return isset(self::DRIVERS[strtolower(trim($gateway))]);
    }
}
