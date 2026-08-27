<?php

declare(strict_types=1);

namespace App\Application\Subscription\Actions;

use App\Domain\Subscription\Contracts\SubscriptionReader;

final class CheckSubscriptionLimit
{
    public function __construct(
        private readonly SubscriptionReader $subscriptions,
    ) {}

    /** @return array<string, mixed> */
    public function execute(string $action): array
    {
        return $this->subscriptions->checkLimit($action);
    }
}
