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

    public function handle(): int
    {
        $this->info('Checking subscription lifecycles...');
        $now = now();
        $processed = 0;

        $trialExpired = DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', $now)
            ->get();

        foreach ($trialExpired as $sub) {
            $this->transitionFromAnchor($sub->id, $sub->tenant_id, $sub->trial_ends_at, 'trial→read_only');
            $processed++;
        }

        $activeExpired = DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $now)
            ->get();

        foreach ($activeExpired as $sub) {
            $this->transitionFromAnchor($sub->id, $sub->tenant_id, $sub->ends_at, 'active→read_only');
            $processed++;
        }

        $legacy = DB::connection('mysql')
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

        $readOnlyExpired = DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->where('status', 'read_only')
            ->whereNotNull('read_only_ends_at')
            ->where('read_only_ends_at', '<=', $now)
            ->get();

        foreach ($readOnlyExpired as $sub) {
            DB::connection('mysql')
                ->table('tenant_subscriptions')
                ->where('id', $sub->id)
                ->update([
                    'status' => 'locked',
                    'locked_at' => $sub->locked_at ?? $now,
                    'updated_at' => $now,
                ]);

            $this->line("  [read_only→locked] Tenant: {$sub->tenant_id}");
            Log::info("Subscription {$sub->id} (tenant: {$sub->tenant_id}) transitioned read_only→locked.");
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

        DB::connection('mysql')
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
