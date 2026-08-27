<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Contracts;

interface SubscriptionAccessReader
{
    /**
     * Return the latest subscription state used by access-control middleware.
     *
     * @return array<string, mixed>|null
     */
    public function currentState(): ?array;
}
