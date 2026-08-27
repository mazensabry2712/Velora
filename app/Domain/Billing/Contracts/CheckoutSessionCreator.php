<?php

declare(strict_types=1);

namespace App\Domain\Billing\Contracts;

use App\Application\Billing\DTOs\CheckoutData;

interface CheckoutSessionCreator
{
    /** @param array<string, mixed> $metadata */
    public function create(CheckoutData $data, string $gateway, array $metadata = []): string;
}
