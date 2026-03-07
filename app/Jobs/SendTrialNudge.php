<?php

namespace App\Jobs;

use App\Mail\TrialNudgeMail;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\UsageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * SendTrialNudge — queued job that initialises the tenant context,
 * collects usage stats, sends a TrialNudgeMail for the given nudge day,
 * and marks the nudge as sent on the TenantSubscription record.
 *
 * Dispatched by ProcessTrialNudges command.
 */
class SendTrialNudge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $tenantSubscriptionId,
        public readonly int $nudgeDay,
    ) {}

    public function handle(): void
    {
        /** @var TenantSubscription|null $sub */
        $sub = TenantSubscription::find($this->tenantSubscriptionId);

        if (! $sub) {
            Log::warning("SendTrialNudge: subscription [{$this->tenantSubscriptionId}] not found.");
            return;
        }

        // Skip if already sent (race-condition guard)
        if ($sub->nudgeSent($this->nudgeDay)) {
            return;
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::find($sub->tenant_id);

        if (! $tenant) {
            Log::warning("SendTrialNudge: tenant [{$sub->tenant_id}] not found.");
            return;
        }

        // Gather stats from tenant DB
        [$businessName, $ownerEmail, $appointmentsCount, $remindersCount, $bookingUrl] =
            $this->collectTenantStats($tenant, $sub);

        if (! $ownerEmail) {
            Log::warning("SendTrialNudge: no owner email for tenant [{$tenant->id}], skipping.");
            return;
        }

        // Send the mail
        try {
            Mail::to($ownerEmail)->send(new TrialNudgeMail(
                nudgeDay:          $this->nudgeDay,
                businessName:      $businessName,
                ownerEmail:        $ownerEmail,
                tenantId:          $tenant->id,
                bookingUrl:        $bookingUrl,
                appointmentsCount: $appointmentsCount,
                remindersCount:    $remindersCount,
                trialDaysLeft:     max(0, $sub->trialDaysLeft()),
            ));

            // Mark nudge sent (central DB)
            $sub->markNudgeSent($this->nudgeDay);

            // Log event in central UsageLog
            UsageLog::log("trial_nudge_day{$this->nudgeDay}_sent", [
                'tenant_id'         => $tenant->id,
                'appointments'      => $appointmentsCount,
                'reminders'         => $remindersCount,
                'trial_days_left'   => max(0, $sub->trialDaysLeft()),
            ]);

            Log::info("SendTrialNudge: Day-{$this->nudgeDay} nudge sent to tenant [{$tenant->id}] ({$ownerEmail}).");
        } catch (\Throwable $e) {
            Log::error("SendTrialNudge: failed for tenant [{$tenant->id}]: " . $e->getMessage());
            throw $e; // re-throw so the queue retries
        }
    }

    /**
     * Initialise tenant context, read stats, end context.
     *
     * @return array{string, string|null, int, int, string}
     *              [businessName, ownerEmail, appointmentsCount, remindersCount, bookingUrl]
     */
    private function collectTenantStats(Tenant $tenant, TenantSubscription $sub): array
    {
        $businessName      = $tenant->name ?? 'عميل Velora';
        $ownerEmail        = null;
        $appointmentsCount = 0;
        $remindersCount    = 0;
        $bookingUrl        = '';

        try {
            tenancy()->initialize($tenant);

            // Owner email from Users table (first admin)
            $owner = \App\Models\User::where('role', 'admin')->first()
                  ?? \App\Models\User::first();
            $ownerEmail = $owner?->email;

            // Stats
            $appointmentsCount = \App\Models\Appointment::count();
            $remindersCount    = class_exists(\App\Models\ReminderLog::class)
                ? \App\Models\ReminderLog::where('status', 'sent')->count()
                : 0;

            // Booking URL — use first active domain
            $domain     = $tenant->domains()->first();
            $bookingUrl = $domain
                ? 'https://' . $domain->domain . '/book'
                : url('/book');

            // Business name override from settings
            $settings            = \App\Models\Setting::first();
            $businessName        = $settings?->business_name ?? $businessName;

            tenancy()->end();
        } catch (\Throwable $e) {
            Log::error("SendTrialNudge.collectTenantStats: tenant [{$tenant->id}]: " . $e->getMessage());
            tenancy()->end();
        }

        return [$businessName, $ownerEmail, $appointmentsCount, $remindersCount, $bookingUrl];
    }
}
