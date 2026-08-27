<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing;

use App\Domain\Subscription\Contracts\SubscriptionAccessReader;
use Illuminate\Support\Facades\DB;

final class LegacySubscriptionAccessReader implements SubscriptionAccessReader
{
    public function currentState(): ?array
    {
        $tenantId = tenant('id');

        if (!$tenantId) {
            return null;
        }

        $subscription = DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->first();

        if (!$subscription) {
            return null;
        }

        return (array) $subscription;
    }
}
