<?php

namespace App\Console\Commands;

use App\Mail\TrialReminderMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendTrialReminders extends Command
{
    protected $signature   = 'subscriptions:send-trial-reminders';
    protected $description = 'Send reminder emails for trials expiring in 3 days and grace period warnings';

    public function handle(): int
    {
        $this->info('Sending trial reminder emails...');
        $now           = now();
        $reminderDate  = $now->copy()->addDays(3);
        $sent          = 0;

        // ── 3-Day Warning ──────────────────────────────────────────────
        $expiringSoon = DB::table('tenant_subscriptions')
            ->where('status', 'trial')
            ->whereBetween('trial_ends_at', [$now, $reminderDate])
            ->get();

        foreach ($expiringSoon as $sub) {
            try {
                $tenantData = DB::table('tenants')
                    ->where('id', $sub->tenant_id)
                    ->value('data');

                $data  = json_decode($tenantData ?? '{}', true);
                $email = $data['email'] ?? null;
                $name  = $data['name'] ?? 'Subscriber';

                if (!$email) continue;

                $daysLeft = (int) $now->diffInDays($sub->trial_ends_at, false);

                Mail::to($email)->send(new TrialReminderMail(
                    type: '3day_warning',
                    businessName: $name,
                    tenantId: $sub->tenant_id,
                    daysLeft: $daysLeft,
                    trialEndsAt: $sub->trial_ends_at,
                ));

                $sent++;
                $this->line("  [3-day warning] Sent to {$email} (tenant: {$sub->tenant_id})");

            } catch (\Exception $e) {
                Log::warning("Failed to send trial reminder to tenant {$sub->tenant_id}: " . $e->getMessage());
            }
        }

        // ── Grace Period Warning ───────────────────────────────────────
        $inGrace = DB::table('tenant_subscriptions')
            ->where('status', 'grace')
            ->where('grace_ends_at', '>', $now)
            ->get();

        foreach ($inGrace as $sub) {
            try {
                $tenantData = DB::table('tenants')
                    ->where('id', $sub->tenant_id)
                    ->value('data');

                $data  = json_decode($tenantData ?? '{}', true);
                $email = $data['email'] ?? null;
                $name  = $data['name'] ?? 'Subscriber';

                if (!$email) continue;

                $daysLeft = (int) $now->diffInDays($sub->grace_ends_at, false);

                Mail::to($email)->send(new TrialReminderMail(
                    type: 'grace_warning',
                    businessName: $name,
                    tenantId: $sub->tenant_id,
                    daysLeft: $daysLeft,
                    trialEndsAt: $sub->grace_ends_at,
                ));

                $sent++;
                $this->line("  [grace warning] Sent to {$email} (tenant: {$sub->tenant_id})");

            } catch (\Exception $e) {
                Log::warning("Failed to send grace reminder to tenant {$sub->tenant_id}: " . $e->getMessage());
            }
        }

        $this->info("Done. Sent {$sent} email(s).");

        return self::SUCCESS;
    }
}
