<?php

declare(strict_types=1);

namespace App\Application\Billing\Actions;

use App\Domain\Billing\Contracts\StripeWebhookProcessor;

final class HandleStripeWebhook
{
    public function __construct(private readonly StripeWebhookProcessor $processor) {}

    /** @return array{status: string, duplicate?: bool} */
    public function execute(string $payload, string $signature): array
    {
        return $this->processor->process($payload, $signature);
    }
}
