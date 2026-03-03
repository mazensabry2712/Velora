<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\ReminderLog;
use App\Models\ReminderRule;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * ProcessReminders — scans active reminder_rules and dispatches reminders.
 *
 * Runs every 15 minutes via schedule.
 * For each active rule (trigger_type = before_appointment):
 *   1. Finds appointments whose time is exactly `trigger_minutes` ahead (±7 min window)
 *   2. Skips if a reminder for same (appointment_id, rule_id, channel) was already sent
 *   3. Sends email via AppointmentReminderMail
 *   4. Logs the send in reminder_logs
 */
class ProcessReminders extends Command
{
    protected $signature   = 'reminders:process {--tenant= : Run for a specific tenant ID}';
    protected $description = 'Send scheduled appointment reminders based on reminder_rules';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        $totalSent   = 0;
        $totalFailed = 0;

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);
                [$sent, $failed] = $this->processTenant();
                $totalSent   += $sent;
                $totalFailed += $failed;
                tenancy()->end();
            } catch (\Throwable $e) {
                Log::error("ProcessReminders: tenant [{$tenant->id}] error: " . $e->getMessage());
                tenancy()->end();
            }
        }

        $this->info("Reminders processed. Sent: {$totalSent}, Failed: {$totalFailed}");

        return self::SUCCESS;
    }

    /**
     * Process reminders for the current tenant context.
     *
     * @return array{int, int} [$sent, $failed]
     */
    private function processTenant(): array
    {
        $now    = Carbon::now();
        $window = 7; // minutes tolerance (±7 min around trigger_minutes)
        $sent   = 0;
        $failed = 0;

        /** @var \Illuminate\Support\Collection<ReminderRule> $rules */
        $rules = ReminderRule::active()
            ->where('trigger_type', 'before_appointment')
            ->where('channel', 'email')
            ->get();

        foreach ($rules as $rule) {
            // target window: appointments that start between (now + trigger_minutes - window) and (now + trigger_minutes + window)
            $windowStart = $now->copy()->addMinutes($rule->trigger_minutes - $window);
            $windowEnd   = $now->copy()->addMinutes($rule->trigger_minutes + $window);

            $appointments = Appointment::with(['customer', 'service', 'staff'])
                ->whereNotIn('status', ['cancelled', 'completed', 'no_show'])
                ->where(function ($q) use ($windowStart, $windowEnd) {
                    // Support both date+time_slot format (legacy) and starts_at (V2)
                    $q->whereBetween('starts_at', [$windowStart, $windowEnd])
                      ->orWhere(function ($q2) use ($windowStart, $windowEnd) {
                          $q2->whereNull('starts_at')
                             ->whereDate('date', $windowStart->toDateString())
                             ->whereRaw("CAST(CONCAT(date, ' ', time_slot) AS DATETIME) BETWEEN ? AND ?", [
                                 $windowStart->toDateTimeString(),
                                 $windowEnd->toDateTimeString(),
                             ]);
                      });
                })
                ->get();

            foreach ($appointments as $appointment) {
                // Dedup: skip if already sent for this rule + appointment
                $alreadySent = ReminderLog::where('appointment_id', $appointment->id)
                    ->where('rule_id', $rule->id)
                    ->where('status', 'sent')
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $this->sendReminder($appointment, $rule, $sent, $failed);
            }
        }

        return [$sent, $failed];
    }

    private function sendReminder(Appointment $appointment, ReminderRule $rule, int &$sent, int &$failed): void
    {
        $customer = $appointment->customer;

        if (! $customer || ! $customer->email) {
            return;
        }

        $log = ReminderLog::create([
            'appointment_id' => $appointment->id,
            'rule_id'        => $rule->id,
            'channel'        => 'email',
            'recipient'      => $customer->email,
            'status'         => 'pending',
            'scheduled_at'   => now(),
        ]);

        try {
            Mail::to($customer->email)->send(
                new \App\Mail\AppointmentReminderMail($appointment, $customer, app()->getLocale())
            );

            $log->update(['status' => 'sent', 'sent_at' => now()]);
            $sent++;

        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
            Log::warning("ProcessReminders: failed to send reminder for appointment [{$appointment->id}]: " . $e->getMessage());
            $failed++;
        }
    }
}
