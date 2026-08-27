<?php

declare(strict_types=1);

namespace App\Application\Subscription\Actions;

use App\Domain\Subscription\Contracts\SubscriptionReader;
use App\Domain\Subscription\Contracts\UpgradeRequestWriter;
use Illuminate\Validation\ValidationException;

final class RequestSubscriptionUpgrade
{
    public function __construct(
        private readonly SubscriptionReader $subscriptions,
        private readonly UpgradeRequestWriter $requests,
    ) {}

    /** @return object */
    public function execute(int $planId, string $tenantId, string $name, string $email, ?string $message = null): object
    {
        $plan = $this->requests->findActivePlan($planId);

        if (! $plan) {
            throw ValidationException::withMessages([
                'plan_id' => ['Plan not found.'],
            ]);
        }

        $current = $this->subscriptions->current() ?? [];

        $this->requests->create([
            'tenant_id' => $tenantId,
            'current_plan_id' => $current['plan_id'] ?? null,
            'requested_plan_id' => $planId,
            'status' => 'pending',
            'requested_by_name' => $name,
            'requested_by_email' => $email,
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $plan;
    }
}
