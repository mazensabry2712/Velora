<?php

declare(strict_types=1);

namespace App\Domain\Billing\Contracts;

interface StripeWebhookProcessor
{
    /**
     * Process a verified Stripe webhook request and return a JSON-ready result.
     *
     * @return array{status: string, duplicate?: bool}
     */
    public function process(string $payload, string $signature): array;
}
