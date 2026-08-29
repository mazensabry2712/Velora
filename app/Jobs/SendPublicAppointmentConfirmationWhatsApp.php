<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Notifications\Contracts\WhatsAppProvider;
use App\Models\NotificationDelivery;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class SendPublicAppointmentConfirmationWhatsApp implements ShouldQueue
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

    public function handle(WhatsAppProvider $provider): void
    {
        $this->tenant->run(function () use ($provider): void {
            $delivery = NotificationDelivery::query()->findOrFail($this->deliveryId);

            if ($delivery->sent_at !== null || $delivery->status === 'skipped') {
                return;
            }

            $delivery->update([
                'status' => 'sending',
                'attempts' => $delivery->attempts + 1,
                'last_error' => null,
            ]);

            try {
                $message = $this->message();
                $result = $provider->send($this->data['recipient'], $message, [
                    'event' => 'appointment.booked',
                    'public_reference' => $this->data['reference'],
                ]);

                if ($result->status === 'sent') {
                    $delivery->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'metadata' => array_merge((array) $delivery->metadata, [
                            'provider_message_id' => $result->providerMessageId,
                        ]),
                    ]);
                    return;
                }

                if ($result->status === 'skipped') {
                    $delivery->update([
                        'status' => 'skipped',
                        'last_error' => $result->error,
                    ]);
                    return;
                }

                throw new \RuntimeException($result->error ?: 'WhatsApp provider failed.');
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

    private function message(): string
    {
        return sprintf(
            "%s\n\nYour appointment is confirmed ✅\n\nService: %s\nSpecialist: %s\nDate: %s\nTime: %s\nQueue: %s\n\nBooking Reference: %s\n\nTrack your appointment:\n%s",
            $this->data['tenant_name'],
            $this->data['service_name'],
            $this->data['staff_name'],
            $this->data['appointment_date'],
            $this->data['appointment_time'],
            $this->data['queue_number'],
            $this->data['reference'],
            $this->data['tracking_url'],
        );
    }
}
