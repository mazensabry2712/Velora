<?php

declare(strict_types=1);

namespace App\Infrastructure\Subscription;

use App\Domain\Subscription\Contracts\SubscriptionAccessReader;
use Illuminate\Support\Facades\DB;

final class EloquentSubscriptionAccessReader implements SubscriptionAccessReader
{
    public function currentState(): ?array
    {
        $tenantId = tenant('id');

        if (! $tenantId) {
            return null;
        }

        $subscription = DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->first();

        return $subscription ? (array) $subscription : null;
    }
}
