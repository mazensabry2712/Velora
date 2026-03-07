<?php

namespace App\Console\Commands;

use App\Jobs\SendTrialNudge;
use App\Mail\FounderAlertMail;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * ProcessTrialNudges — runs daily at 09:00.
 *
 * Checks every active trial subscription and dispatches a
 * SendTrialNudge job for any nudge day (1, 3, 7, 12) that
 * has not yet been sent and whose day threshold has been reached.
 *
 * Nudge schedule (relative to trial_starts_on / activated_at):
 *   Day 1  → send on the day of registration (day 0–1)
 *   Day 3  → send on day 2–3 of trial
 *   Day 7  → send on day 6–7
 *   Day 12 → send on day 11–12
 *
 * Uses a ±1 day window to tolerate cases where the command ran late.
 */
class ProcessTrialNudges extends Command
{
    protected $signature   = 'trial:nudges {--tenant= : Run for a specific tenant ID only}';
    protected $description = 'Dispatch trial nudge emails for active trial tenants';

    /** @var array<int, int[]> nudgeDay => [daysElapsedMin, daysElapsedMax] */
    private const NUDGE_WINDOWS = [
        1  => [0,  1],
        3  => [2,  3],
        7  => [6,  7],
        12 => [11, 13],
    ];

    public function handle(): int
    {
        $tenantIdFilter = $this->option('tenant');

        $query = TenantSubscription::where('status', 'trial')
            ->whereNotNull('trial_starts_on');

        if ($tenantIdFilter) {
            $query->where('tenant_id', $tenantIdFilter);
        }

        $subscriptions = $query->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No active trial subscriptions found.');
            return self::SUCCESS;
        }

        $dispatched = 0;
        $skipped    = 0;

        foreach ($subscriptions as $sub) {
            $elapsed = $this->trialDayElapsed($sub);

            // ── Founder alert at Day 11 (once only) ──────────────────────────
            if ($elapsed >= 11 && ! $sub->founder_alerted) {
                $this->fireFounderAlert($sub, $elapsed);
            }

            foreach (self::NUDGE_WINDOWS as $nudgeDay => [$min, $max]) {
                if ($elapsed < $min || $elapsed > $max) {
                    continue; // outside window
                }
                if ($sub->nudgeSent($nudgeDay)) {
                    $skipped++;
                    continue; // already sent
                }

                SendTrialNudge::dispatch($sub->id, $nudgeDay)
                    ->onQueue('emails');

                $this->line("  → Queued Day-{$nudgeDay} nudge for tenant [{$sub->tenant_id}] (day {$elapsed} of trial)");
                Log::info("ProcessTrialNudges: queued day-{$nudgeDay} for tenant [{$sub->tenant_id}]");

                $dispatched++;
            }
        }

        $this->info("Done. Dispatched: {$dispatched}, already-sent skipped: {$skipped}.");
        return self::SUCCESS;
    }

    /**
     * Calculate how many full days have elapsed since the trial started.
     * Uses `activated_at` if available, otherwise `trial_starts_on`.
     */
    private function trialDayElapsed(TenantSubscription $sub): int
    {
        $startDate = $sub->activated_at
            ?? Carbon::parse($sub->trial_starts_on);

        return (int) Carbon::now()->diffInDays($startDate, false) * -1;
    }

    /**
     * Send a one-time founder alert when a trial hits Day 11 without converting.
     */
    private function fireFounderAlert(TenantSubscription $sub, int $elapsed): void
    {
        $founderEmail = config('mail.founder_email', config('mail.from.address'));
        if (! $founderEmail) {
            return;
        }

        try {
            $sub->update(['founder_alerted' => true]);

            $tenant = DB::connection(config('tenancy.database.central_connection', 'mysql'))
                ->table('tenants')
                ->where('id', $sub->tenant_id)
                ->first();

            Mail::to($founderEmail)->queue(new FounderAlertMail(
                tenantId:      $sub->tenant_id,
                businessName:  $tenant?->name ?? $sub->tenant_id,
                ownerEmail:    $tenant?->email ?? 'unknown',
                triggerReason: 'Day 11 — no upgrade yet',
                trialDaysLeft: $sub->trialDaysLeft(),
                stats:         [
                    'Trial Day'  => $elapsed,
                    'Aha Moment' => $sub->aha_reached ? 'Yes' : 'No',
                    'Nudges Sent' => count(array_filter($sub->nudges_sent ?? [])),
                ],
            ));

            $this->line("  → Founder alert sent for tenant [{$sub->tenant_id}] (day {$elapsed})");
        } catch (\Exception $e) {
            Log::error('ProcessTrialNudges founder alert failed: ' . $e->getMessage());
        }
    }
}
