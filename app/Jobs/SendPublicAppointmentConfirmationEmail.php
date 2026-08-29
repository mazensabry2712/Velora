<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PublicAppointmentConfirmationMail;
use App\Models\NotificationDelivery;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendPublicAppointmentConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    /** @param array<string, string> $data */
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
                $mail = new PublicAppointmentConfirmationMail(
                    tenantName: $this->data['tenant_name'],
                    customerName: $this->data['customer_name'],
                    serviceName: $this->data['service_name'],
                    staffName: $this->data['staff_name'],
                    appointmentDate: $this->data['appointment_date'],
                    appointmentTime: $this->data['appointment_time'],
                    duration: $this->data['duration'],
                    queueNumber: $this->data['queue_number'],
                    reference: $this->data['reference'],
                    trackingUrl: $this->data['tracking_url'],
                    mailLocale: $this->data['locale'],
                );

                Mail::to($this->data['recipient'])->send($mail);

                $delivery->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } catch (Throwable $e) {
                $delivery->update([
                    'status' => 'queued',
                    'last_error' => mb_substr($e->getMessage(), 0, 5000),
                ]);

                throw $e;
            }
        });
    }

    public function failed(Throwable $e): void
    {
        $this->tenant->run(function () use ($e): void {
            NotificationDelivery::query()->whereKey($this->deliveryId)->update([
                'status' => 'failed',
                'failed_at' => now(),
                'last_error' => mb_substr($e->getMessage(), 0, 5000),
            ]);
        });
    }
}
