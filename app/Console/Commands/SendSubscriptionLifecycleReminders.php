<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\TrialReminderMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendSubscriptionLifecycleReminders extends Command
{
    protected $signature = 'subscriptions:send-lifecycle-reminders';
    protected $description = 'Send reminders for trial, read-only and permanent-deletion deadlines';

    private function centralConnection(): string
    {
        return (string) config('tenancy.database.central_connection', 'mysql');
    }

    public function handle(): int
    {
        $this->info('Sending subscription lifecycle reminders...');
        $now = now();
        $sent = 0;
        $connection = DB::connection($this->centralConnection());

        $trialRows = $connection
            ->table('tenant_subscriptions')
            ->where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [$now, $now->copy()->addDays(3)])
            ->get();

        foreach ($trialRows as $row) {
            $sent += $this->send($row, '3day_warning', $row->trial_ends_at);
        }

        $readOnlyRows = $connection
            ->table('tenant_subscriptions')
            ->where('status', 'read_only')
            ->whereNotNull('read_only_ends_at')
            ->whereBetween('read_only_ends_at', [$now, $now->copy()->addDays(7)])
            ->get();

        foreach ($readOnlyRows as $row) {
            $sent += $this->send($row, 'read_only_warning', $row->read_only_ends_at);
        }

        $lockedRows = $connection
            ->table('tenant_subscriptions')
            ->where('status', 'locked')
            ->whereNotNull('deletion_at')
            ->whereBetween('deletion_at', [$now, $now->copy()->addDays(7)])
            ->get();

        foreach ($lockedRows as $row) {
            $sent += $this->send($row, 'deletion_warning', $row->deletion_at);
        }

        $this->info("Done. Queued {$sent} email(s).");
        return self::SUCCESS;
    }

    private function send(object $subscription, string $type, string $deadline): int
    {
        try {
            $tenantData = DB::connection($this->centralConnection())
                ->table('tenants')
                ->where('id', $subscription->tenant_id)
                ->value('data');

            $data = json_decode($tenantData ?? '{}', true) ?: [];
            $email = $data['email'] ?? null;
            $name = $data['name'] ?? 'Subscriber';

            if (! $email) {
                return 0;
            }

            $daysLeft = max(0, (int) now()->diffInDays(now()->parse($deadline), false));

            Mail::to($email)->queue(new TrialReminderMail(
                type: $type,
                businessName: $name,
                tenantId: $subscription->tenant_id,
                daysLeft: $daysLeft,
                trialEndsAt: $deadline,
            ));

            return 1;
        } catch (\Throwable $e) {
            Log::warning('Subscription lifecycle reminder failed.', [
                'tenant_id' => $subscription->tenant_id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
