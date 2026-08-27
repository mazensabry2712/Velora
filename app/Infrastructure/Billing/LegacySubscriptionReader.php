<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing;

use App\Domain\Subscription\Contracts\SubscriptionReader;
use App\Services\SubscriptionService;

final class LegacySubscriptionReader implements SubscriptionReader
{
    public function __construct(
        private readonly SubscriptionService $subscription,
    ) {}

    public function current(): ?array
    {
        return $this->subscription->getSubscriptionInfo();
    }

    public function usage(): array
    {
        return $this->subscription->calculateUsage();
    }

    public function availableUpgrades(): array
    {
        return $this->subscription->getAvailableUpgrades();
    }

    public function invoices(int $limit = 20): array
    {
        return $this->subscription->getInvoices($limit)->all();
    }

    public function checkLimit(string $action): array
    {
        return $this->subscription->canPerformAction($action);
    }

    public function hasFeature(string $feature): bool
    {
        return $this->subscription->hasFeature($feature);
    }
}
