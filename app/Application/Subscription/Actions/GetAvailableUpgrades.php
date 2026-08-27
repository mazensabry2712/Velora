<?php

declare(strict_types=1);

namespace App\Application\Subscription\Actions;

use App\Domain\Subscription\Contracts\SubscriptionReader;

final class GetAvailableUpgrades
{
    public function __construct(
        private readonly SubscriptionReader $subscriptions,
    ) {}

    /** @return array<string, mixed> */
    public function execute(): array
    {
        return $this->subscriptions->availableUpgrades();
    }
}
