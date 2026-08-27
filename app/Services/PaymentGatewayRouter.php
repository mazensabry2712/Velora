<?php

declare(strict_types=1);

namespace App\Services;

/**
 * @deprecated Use App\Infrastructure\Payments\PaymentGatewayRouter through
 * App\Domain\Shared\Contracts\PaymentGatewayResolver instead.
 */
class_alias(
    \App\Infrastructure\Payments\PaymentGatewayRouter::class,
    __NAMESPACE__ . '\\PaymentGatewayRouter',
);
