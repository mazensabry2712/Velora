<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Subscription\SubscriptionLifecycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CheckSubscriptionStatus extends Command
{
    protected $signature = 'subscriptions:check-status';
    protected $description = 'Transition subscriptions through trial, read-only and locked lifecycle states';

    private function centralConnection(): string
    {
        return (string) config('tenancy.database.central_connection', 'mysql');
    }

    public function handle(): int
    {
        $this->info('Checking subscription lifecycles...');
        $now = now();
        $processed = 0;
        $connection = DB::connection($this->centralConnection());

        $trialExpired = $connection
            ->table('tenant_subscriptions')
            ->where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', $now)
            ->get();

        foreach ($trialExpired as $sub) {
            $this->transitionFromAnchor($sub->id, $sub->tenant_id, $sub->trial_ends_at, 'trial→read_only');
            $processed++;
        }

        $activeExpired = $connection
            ->table('tenant_subscriptions')
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $now)
            ->get();

        foreach ($activeExpired as $sub) {
            $this->transitionFromAnchor($sub->id, $sub->tenant_id, $sub->ends_at, 'active→read_only');
            $processed++;
        }

        $legacy = $connection
            ->table('tenant_subscriptions')
            ->whereIn('status', ['grace', 'expired'])
            ->get();

        foreach ($legacy as $sub) {
            $anchor = $sub->trial_ends_at ?? $sub->ends_at;
            if (! $anchor && $sub->grace_ends_at) {
                $anchor = now()->parse($sub->grace_ends_at)->subDays(3);
            }

            if ($anchor) {
                $this->transitionFromAnchor($sub->id, $sub->tenant_id, $anchor, 'legacy→lifecycle');
                $processed++;
            }
        }

        $readOnlyExpired = $connection
            ->table('tenant_subscriptions')
            ->where('status', 'read_only')
            ->whereNotNull('read_only_ends_at')
            ->where('read_only_ends_at', '<=', $now)
            ->get();

        foreach ($readOnlyExpired as $sub) {
            $lockedAt = $sub->locked_at
                ? now()->parse($sub->locked_at)
                : now()->parse($sub->read_only_ends_at);
            $deletionAt = $sub->deletion_at
                ? now()->parse($sub->deletion_at)
                : $lockedAt->copy()->addDays(SubscriptionLifecycle::LOCKED_DAYS);

            $connection
                ->table('tenant_subscriptions')
                ->where('id', $sub->id)
                ->update([
                    'status' => 'locked',
                    'locked_at' => $lockedAt,
                    'deletion_at' => $deletionAt,
                    'updated_at' => $now,
                ]);

            $this->line("  [read_only→locked] Tenant: {$sub->tenant_id}");
            Log::info("Subscription {$sub->id} (tenant: {$sub->tenant_id}) transitioned read_only→locked.", [
                'locked_at' => $lockedAt->toDateTimeString(),
                'deletion_at' => $deletionAt->toDateTimeString(),
            ]);
            $processed++;
        }

        $this->info("Done. Processed {$processed} subscription(s).");
        return self::SUCCESS;
    }

    private function transitionFromAnchor(int $subscriptionId, string $tenantId, string $anchor, string $label): void
    {
        $anchorAt = now()->parse($anchor);
        $readOnlyEndsAt = SubscriptionLifecycle::readOnlyEndsAt($anchorAt);
        $deletionAt = SubscriptionLifecycle::deletionAt($anchorAt);
        $now = now();
        $status = $now->lt($readOnlyEndsAt) ? 'read_only' : 'locked';

        DB::connection($this->centralConnection())
            ->table('tenant_subscriptions')
            ->where('id', $subscriptionId)
            ->update([
                'status' => $status,
                'read_only_ends_at' => $readOnlyEndsAt,
                'locked_at' => $readOnlyEndsAt,
                'deletion_at' => $deletionAt,
                'grace_ends_at' => null,
                'updated_at' => $now,
            ]);

        $this->line("  [{$label}] Tenant: {$tenantId}");
        Log::info("Subscription {$subscriptionId} (tenant: {$tenantId}) transitioned {$label}.", [
            'status' => $status,
            'read_only_ends_at' => $readOnlyEndsAt->toDateTimeString(),
            'deletion_at' => $deletionAt->toDateTimeString(),
        ]);
    }
}
