<?php

declare(strict_types=1);

namespace App\Domain\Billing\Contracts;

interface MoyasarWebhookProcessor
{
    /** @return array{status: string, duplicate?: bool} */
    public function process(string $payload, ?string $signature): array;
}
