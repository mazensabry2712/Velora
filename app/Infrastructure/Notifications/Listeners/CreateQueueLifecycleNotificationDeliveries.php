<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Listeners;

use App\Domain\Queue\Events\QueueLifecycleNotificationRequested;
use App\Jobs\SendQueueLifecycleNotification;
use App\Models\NotificationDelivery;
use App\Models\Tenant;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\QueryException;

final class CreateQueueLifecycleNotificationDeliveries implements ShouldHandleEventsAfterCommit
{
    public function handle(QueueLifecycleNotificationRequested $event): void
    {
        $tenant = Tenant::query()->findOrFail($event->tenantId);

        $channels = [];

        if ($event->email !== null && trim($event->email) !== '') {
            $channels[] = [
                'channel' => 'email',
                'recipient' => $event->email,
                'provider' => 'mail',
            ];
        }

        if ($event->phone !== null && trim($event->phone) !== '') {
            $channels[] = [
                'channel' => 'whatsapp',
                'recipient' => $event->phone,
                'provider' => 'whatsapp',
            ];
        }

        foreach ($channels as $channel) {
            $dedupeKey = $this->dedupeKey($event, $channel['channel']);

            if (NotificationDelivery::query()->where('dedupe_key', $dedupeKey)->exists()) {
                continue;
            }

            try {
                $delivery = NotificationDelivery::query()->create([
                    'appointment_id' => $event->appointmentId,
                    'public_reference' => $event->publicReference,
                    'event' => $event->event,
                    'channel' => $channel['channel'],
                    'recipient' => $channel['recipient'],
                    'provider' => $channel['provider'],
                    'status' => 'queued',
                    'attempts' => 0,
                    'dedupe_key' => $dedupeKey,
                    'queued_at' => now(),
                    'metadata' => [
                        'event_id' => $event->eventId,
                        'queue_id' => $event->queueId,
                        'position' => $event->position,
                        'old_position' => $event->oldPosition,
                        'update_type' => $event->updateType,
                        'customer_type' => $event->customerType,
                        'customer_id' => $event->customerId,
                        'locale' => $event->locale,
                    ],
                ]);
            } catch (QueryException $exception) {
                if ($this->isUniqueViolation($exception)) {
                    continue;
                }

                throw $exception;
            }

            SendQueueLifecycleNotification::dispatch(
                tenant: $tenant,
                deliveryId: (int) $delivery->id,
                data: [
                    'event_id' => $event->eventId,
                    'queue_id' => $event->queueId,
                    'appointment_id' => $event->appointmentId,
                    'public_reference' => $event->publicReference,
                    'event' => $event->event,
                    'update_type' => $event->updateType,
                    'queue_number' => $event->queueNumber,
                    'position' => $event->position,
                    'old_position' => $event->oldPosition,
                    'customer_type' => $event->customerType,
                    'customer_id' => $event->customerId,
                    'customer_name' => $event->customerName,
                    'recipient' => $channel['recipient'],
                    'locale' => $event->locale,
                    'channel' => $channel['channel'],
                ],
            );
        }
    }

    private function dedupeKey(QueueLifecycleNotificationRequested $event, string $channel): string
    {
        $key = $event->event . '|' . $channel . '|' . $event->publicReference;

        if ($event->event === 'queue.position_changed') {
            $key .= '|' . $event->eventId;
        }

        return $key;
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return $sqlState === '23000';
    }
}
