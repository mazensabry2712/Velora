<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\AppointmentReminderMail;
use App\Models\Customer;
use App\Models\NotificationDelivery;
use App\Models\ReminderLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendAppointmentReminderEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    /** @param array<string, int|string|null> $data */
    public function __construct(
        public readonly Tenant $tenant,
        public readonly int $deliveryId,
        public readonly array $data,
    ) {}

    public function handle(): void
    {
        $this->tenant->run(function (): void {
            $delivery = NotificationDelivery::query()->findOrFail($this->deliveryId);

            if ($delivery->sent_at !== null) {
                return;
            }

            $delivery->update([
                'status' => 'sending',
                'attempts' => $delivery->attempts + 1,
                'last_error' => null,
            ]);

            try {
                $appointment = \App\Models\Appointment::query()
                    ->findOrFail((int) $this->data['appointment_id']);

                $customer = $this->resolveCustomer();

                Mail::to((string) $this->data['recipient'])->send(
                    new AppointmentReminderMail(
                        $appointment,
                        $customer,
                        (string) ($this->data['locale'] ?? 'en'),
                        isset($this->data['tracking_url']) ? (string) $this->data['tracking_url'] : null,
                    )
                );

                $delivery->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                $reminderLogId = (int) ($this->data['reminder_log_id'] ?? 0);
                if ($reminderLogId > 0) {
                    ReminderLog::query()->whereKey($reminderLogId)->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);
                }
            } catch (Throwable $e) {
                $error = mb_substr($e->getMessage(), 0, 5000);

                $delivery->update([
                    'status' => 'queued',
                    'last_error' => $error,
                ]);

                $reminderLogId = (int) ($this->data['reminder_log_id'] ?? 0);
                if ($reminderLogId > 0) {
                    ReminderLog::query()->whereKey($reminderLogId)->update([
                        'status' => 'pending',
                        'error' => $error,
                    ]);
                }

                throw $e;
            }
        });
    }

    public function failed(Throwable $e): void
    {
        $this->tenant->run(function () use ($e): void {
            $error = mb_substr($e->getMessage(), 0, 5000);

            NotificationDelivery::query()->whereKey($this->deliveryId)->update([
                'status' => 'failed',
                'failed_at' => now(),
                'last_error' => $error,
            ]);

            $reminderLogId = (int) ($this->data['reminder_log_id'] ?? 0);
            if ($reminderLogId > 0) {
                ReminderLog::query()->whereKey($reminderLogId)->update([
                    'status' => 'failed',
                    'error' => $error,
                ]);
            }
        });
    }

    private function resolveCustomer(): User|Customer
    {
        if (($this->data['customer_type'] ?? 'customer') === 'user') {
            return User::query()->findOrFail((int) $this->data['customer_id']);
        }

        return Customer::query()->findOrFail((int) $this->data['customer_id']);
    }
}
