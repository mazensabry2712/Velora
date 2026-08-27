<?php

declare(strict_types=1);

namespace App\Domain\Billing\Contracts;

interface CheckoutSessionCreator
{
    /** @param array<string, mixed> $data */
    public function create(array $data, string $gateway): string;
}
