<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Notifications\Contracts\WhatsAppProvider;
use App\Mail\QueueLifecycleNotificationMail;
use App\Models\NotificationDelivery;
use App\Models\Tenant;
use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendQueueLifecycleNotification implements ShouldQueue
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

    public function handle(WhatsAppProvider $whatsAppProvider): void
    {
        $this->inTenantContext(function () use ($whatsAppProvider): void {
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
                if (($this->data['channel'] ?? 'email') === 'whatsapp') {
                    $this->sendWhatsApp($whatsAppProvider);
                } else {
                    $this->sendEmail();
                }

                $delivery->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } catch (Throwable $e) {
                $error = mb_substr($e->getMessage(), 0, 5000);

                $delivery->update([
                    'status' => 'queued',
                    'last_error' => $error,
                ]);

                throw $e;
            }
        });
    }

    public function failed(Throwable $e): void
    {
        $this->inTenantContext(function () use ($e): void {
            NotificationDelivery::query()->whereKey($this->deliveryId)->update([
                'status' => 'failed',
                'failed_at' => now(),
                'last_error' => mb_substr($e->getMessage(), 0, 5000),
            ]);
        });
    }

    private function sendEmail(): void
    {
        Mail::to((string) $this->data['recipient'])->send(
            new QueueLifecycleNotificationMail(
                customerName: (string) $this->data['customer_name'],
                updateType: (string) $this->data['update_type'],
                queueNumber: (string) $this->data['queue_number'],
                position: isset($this->data['position']) ? (int) $this->data['position'] : null,
                locale: (string) ($this->data['locale'] ?? config('app.locale', 'ar')),
            )
        );
    }

    private function sendWhatsApp(WhatsAppProvider $provider): void
    {
        $locale = (string) ($this->data['locale'] ?? config('app.locale', 'ar'));
        $updateType = (string) $this->data['update_type'];
        $queueNumber = (string) $this->data['queue_number'];
        $position = isset($this->data['position']) ? (int) $this->data['position'] : null;

        $lines = [
            __('notifications.queue_' . $updateType . '.greeting', [
                'name' => (string) $this->data['customer_name'],
            ], $locale),
            __('notifications.queue_' . $updateType . '.message', [], $locale),
            __('notifications.queue_' . $updateType . '.queue_number', [
                'number' => $queueNumber,
            ], $locale),
        ];

        if ($updateType === 'position_update' && $position !== null) {
            $lines[] = __('notifications.queue_position_update.position', [
                'position' => $position,
            ], $locale);
        }

        if ($updateType === 'ready') {
            $lines[] = __('notifications.queue_ready.position', [], $locale);
        }

        $result = $provider->send(
            (string) $this->data['recipient'],
            implode("\n", $lines),
            [
                'event' => (string) $this->data['event'],
                'event_id' => (string) $this->data['event_id'],
                'public_reference' => (string) $this->data['public_reference'],
                'queue_number' => $queueNumber,
                'position' => $position,
            ],
        );

        if ($result->status === 'skipped') {
            $this->markSkipped($result->error);
            return;
        }

        if ($result->status === 'failed') {
            throw new \RuntimeException($result->error ?: 'WhatsApp provider failed.');
        }

        $delivery = NotificationDelivery::query()->findOrFail($this->deliveryId);
        $metadata = $delivery->metadata ?? [];
        $metadata['provider_message_id'] = $result->providerMessageId;
        $metadata['provider_status'] = $result->status;
        $delivery->update(['metadata' => $metadata]);
    }

    private function markSkipped(?string $reason): void
    {
        $delivery = NotificationDelivery::query()->findOrFail($this->deliveryId);
        $metadata = $delivery->metadata ?? [];
        $metadata['provider_status'] = 'skipped';
        $metadata['provider_reason'] = $reason;

        $delivery->update([
            'status' => 'skipped',
            'metadata' => $metadata,
        ]);
    }

    private function inTenantContext(Closure $callback): void
    {
        $currentTenant = tenant();

        if ($currentTenant && (string) $currentTenant->getKey() === (string) $this->tenant->getKey()) {
            $callback();
            return;
        }

        $this->tenant->run($callback);
    }
}
