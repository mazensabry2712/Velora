<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendAppointmentReminderEmail;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\NotificationDelivery;
use App\Models\ReminderLog;
use App\Models\ReminderRule;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Scan active appointment reminder rules and dispatch idempotent delivery jobs.
 *
 * Runs every 15 minutes via the application scheduler. Delivery itself is
 * handled by SendAppointmentReminderEmail so this command never performs
 * external I/O directly.
 */
final class ProcessReminders extends Command
{
    protected $signature = 'reminders:process {--tenant= : Run for a specific tenant ID}';

    protected $description = 'Dispatch scheduled appointment reminders';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $tenants = $tenantId
            ? Tenant::query()->whereKey($tenantId)->get()
            : Tenant::query()->get();

        $totalQueued = 0;
        $totalSkipped = 0;
        $totalFailed = 0;

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);
                [$queued, $skipped, $failed] = $this->processTenant($tenant);
                $totalQueued += $queued;
                $totalSkipped += $skipped;
                $totalFailed += $failed;
            } catch (\Throwable $e) {
                $totalFailed++;
                Log::error("ProcessReminders: tenant [{$tenant->id}] error: {$e->getMessage()}");
            } finally {
                tenancy()->end();
            }
        }

        $this->info(
            "Appointment reminders processed. Queued: {$totalQueued}, Skipped: {$totalSkipped}, Failed: {$totalFailed}"
        );

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @return array{int, int, int} [$queued, $skipped, $failed] */
    private function processTenant(Tenant $tenant): array
    {
        $now = Carbon::now();
        $window = 7;
        $queued = 0;
        $skipped = 0;
        $failed = 0;

        $rules = ReminderRule::active()
            ->where('trigger_type', 'before_appointment')
            ->where('channel', 'email')
            ->where('send_to_customer', true)
            ->get();

        foreach ($rules as $rule) {
            $windowStart = $now->copy()->addMinutes((int) $rule->trigger_minutes - $window);
            $windowEnd = $now->copy()->addMinutes((int) $rule->trigger_minutes + $window);

            $appointments = Appointment::with([
                'customer',
                'newCustomer',
                'service',
                'staff',
                'newStaff',
                'queue',
            ])
                ->whereNotIn('status', [
                    Appointment::STATUS_CANCELLED,
                    Appointment::STATUS_COMPLETED,
                    Appointment::STATUS_NO_SHOW,
                ])
                ->where(function ($query) use ($windowStart, $windowEnd): void {
                    $query->whereBetween('starts_at', [$windowStart, $windowEnd])
                        ->orWhere(function ($legacy) use ($windowStart, $windowEnd): void {
                            $legacy->whereNull('starts_at')
                                ->whereDate('date', $windowStart->toDateString())
                                ->whereRaw(
                                    "CAST(CONCAT(date, ' ', time_slot) AS DATETIME) BETWEEN ? AND ?",
                                    [
                                        $windowStart->toDateTimeString(),
                                        $windowEnd->toDateTimeString(),
                                    ]
                                );
                        });
                })
                ->get();

            foreach ($appointments as $appointment) {
                try {
                    if ($this->dispatchReminder($appointment, $rule, $tenant)) {
                        $queued++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning(
                        "ProcessReminders: appointment [{$appointment->id}] failed: {$e->getMessage()}"
                    );
                }
            }
        }

        return [$queued, $skipped, $failed];
    }

    private function dispatchReminder(
        Appointment $appointment,
        ReminderRule $rule,
        Tenant $tenant,
    ): bool {
        $customer = $appointment->newCustomer ?: $appointment->customer;

        if (! $customer || ! $customer->email || ! $appointment->public_reference) {
            return false;
        }

        $event = match ((int) $rule->trigger_minutes) {
            1440 => 'appointment.reminder_24h',
            60 => 'appointment.reminder_1h',
            default => 'appointment.reminder_' . (int) $rule->trigger_minutes . 'm',
        };

        $dedupeKey = "{$event}|email|{$appointment->public_reference}";

        if (NotificationDelivery::query()->where('dedupe_key', $dedupeKey)->exists()) {
            return false;
        }

        $recipient = (string) $customer->email;
        $customerType = $customer instanceof User ? 'user' : 'customer';
        $customerId = (int) $customer->getKey();
        $locale = $customer instanceof Customer && is_string($customer->language) && $customer->language !== ''
            ? $customer->language
            : (app()->getLocale() ?: 'en');
        $trackingUrl = $this->trackingUrl($tenant, $appointment->public_reference);

        $log = ReminderLog::create([
            'appointment_id' => $appointment->id,
            'rule_id' => $rule->id,
            'channel' => 'email',
            'recipient' => $recipient,
            'status' => 'pending',
            'scheduled_at' => now(),
        ]);

        try {
            $delivery = NotificationDelivery::create([
                'appointment_id' => $appointment->id,
                'public_reference' => $appointment->public_reference,
                'event' => $event,
                'channel' => 'email',
                'recipient' => $recipient,
                'provider' => 'mail',
                'status' => 'queued',
                'attempts' => 0,
                'dedupe_key' => $dedupeKey,
                'queued_at' => now(),
                'metadata' => [
                    'rule_id' => $rule->id,
                    'trigger_minutes' => (int) $rule->trigger_minutes,
                    'reminder_log_id' => $log->id,
                    'tracking_url' => $trackingUrl,
                ],
            ]);

            SendAppointmentReminderEmail::dispatch(
                tenant: $tenant,
                deliveryId: (int) $delivery->id,
                data: [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $customerId,
                    'customer_type' => $customerType,
                    'reminder_log_id' => $log->id,
                    'recipient' => $recipient,
                    'locale' => $locale,
                    'tracking_url' => $trackingUrl,
                ],
            );

            return true;
        } catch (QueryException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                $log->delete();
                return false;
            }

            throw $e;
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 5000),
            ]);

            throw $e;
        }
    }

    private function trackingUrl(Tenant $tenant, string $reference): string
    {
        try {
            $domain = $tenant->domains()->first()?->domain;

            if (is_string($domain) && $domain !== '') {
                $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

                return $scheme . '://' . $domain . '/queue/status?ref=' . rawurlencode($reference);
            }
        } catch (\Throwable $e) {
            Log::notice(
                "ProcessReminders: failed to resolve tenant domain for [{$tenant->id}]: {$e->getMessage()}"
            );
        }

        $baseUrl = rtrim((string) config('app.url', 'http://localhost'), '/');

        return $baseUrl . '/queue/status?ref=' . rawurlencode($reference);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'unique') || str_contains($message, 'duplicate');
    }
}
