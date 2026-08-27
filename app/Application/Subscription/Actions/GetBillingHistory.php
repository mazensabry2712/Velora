<?php

declare(strict_types=1);

namespace App\Application\Subscription\Actions;

use App\Domain\Subscription\Contracts\SubscriptionReader;
use Illuminate\Support\Collection;

final class GetBillingHistory
{
    public function __construct(
        private readonly SubscriptionReader $subscriptions,
    ) {}

    /** @return Collection<int, mixed> */
    public function execute(int $limit = 20): Collection
    {
        return collect($this->subscriptions->invoices($limit));
    }
}
