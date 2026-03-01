<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionStatus extends Command
{
    protected $signature   = 'subscriptions:check-status';
    protected $description = 'Transition subscriptions: trial→grace, grace→expired, active→grace';

    public function handle(): int
    {
        $this->info('Checking subscription statuses...');
        $now = now();

        // 1. trial → grace (when trial_ends_at has passed)
        $trialExpired = DB::table('tenant_subscriptions')
            ->where('status', 'trial')
            ->where('trial_ends_at', '<', $now)
            ->get();

        foreach ($trialExpired as $sub) {
            DB::table('tenant_subscriptions')
                ->where('id', $sub->id)
                ->update([
                    'status'        => 'grace',
                    'grace_ends_at' => $now->copy()->addDays(3),
                    'updated_at'    => $now,
                ]);

            $this->line("  [trial→grace] Tenant: {$sub->tenant_id}");
            Log::info("Subscription {$sub->id} (tenant: {$sub->tenant_id}) transitioned trial→grace.");
        }

        // 2. active → grace (when ends_at has passed)
        $activeExpired = DB::table('tenant_subscriptions')
            ->where('status', 'active')
            ->where('ends_at', '<', $now)
            ->whereNotNull('ends_at')
            ->get();

        foreach ($activeExpired as $sub) {
            DB::table('tenant_subscriptions')
                ->where('id', $sub->id)
                ->update([
                    'status'        => 'grace',
                    'grace_ends_at' => $now->copy()->addDays(3),
                    'updated_at'    => $now,
                ]);

            $this->line("  [active→grace] Tenant: {$sub->tenant_id}");
        }

        // 3. grace → expired (when grace_ends_at has passed)
        $graceExpired = DB::table('tenant_subscriptions')
            ->where('status', 'grace')
            ->where('grace_ends_at', '<', $now)
            ->get();

        foreach ($graceExpired as $sub) {
            DB::table('tenant_subscriptions')
                ->where('id', $sub->id)
                ->update([
                    'status'     => 'expired',
                    'updated_at' => $now,
                ]);

            $this->line("  [grace→expired] Tenant: {$sub->tenant_id}");
            Log::warning("Subscription {$sub->id} (tenant: {$sub->tenant_id}) transitioned grace→expired.");
        }

        $total = count($trialExpired) + count($activeExpired) + count($graceExpired);
        $this->info("Done. Processed {$total} subscription(s).");

        return self::SUCCESS;
    }
}
