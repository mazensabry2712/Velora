<?php

declare(strict_types=1);

namespace App\Application\Subscription\Actions;

use App\Domain\Subscription\Contracts\SubscriptionReader;

final class GetSubscriptionOverview
{
    public function __construct(
        private readonly SubscriptionReader $subscriptions,
    ) {}

    /** @return array<string, mixed>|null */
    public function execute(): ?array
    {
        return $this->subscriptions->current();
    }
}
