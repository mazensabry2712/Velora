<?php

declare(strict_types=1);

namespace App\Application\Subscription\Events;

final readonly class SubscriptionUpgradeRequested
{
    public function __construct(
        public string $tenantId,
        public string $requesterName,
        public string $requesterEmail,
        public string $currentPlanName,
        public int $requestedPlanId,
        public string $requestedPlanName,
        public string $requestedPlanPrice,
    ) {}
}
