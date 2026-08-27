<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing;

use App\Domain\Billing\Contracts\TrialExtender;
use Illuminate\Support\Facades\DB;

final class EloquentTrialExtender implements TrialExtender
{
    public function extend(string|int $tenantId, int $days = 7): array
    {
        $connection = config('tenancy.database.central_connection', 'mysql');

        $subscription = DB::connection($connection)
            ->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'trial')
            ->orderByDesc('created_at')
            ->first();

        if (! $subscription) {
            return ['status' => 'missing'];
        }

        if ($subscription->trial_extended) {
            return ['status' => 'already_extended'];
        }

        $newTrialEndsAt = now()->parse($subscription->trial_ends_at)->addDays($days);
        $now = now();

        DB::connection($connection)
            ->table('tenant_subscriptions')
            ->where('id', $subscription->id)
            ->update([
                'trial_ends_at' => $newTrialEndsAt,
                'trial_extended' => true,
                'trial_extended_at' => $now,
                'updated_at' => $now,
            ]);

        return [
            'status' => 'extended',
            'new_trial_ends_at' => $newTrialEndsAt,
        ];
    }
}
