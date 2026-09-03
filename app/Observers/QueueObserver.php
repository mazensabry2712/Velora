<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domain\Queue\Events\QueueLifecycleNotificationRequested;
use App\Models\Queue;
use Illuminate\Support\Str;

final class QueueObserver
{
    public function created(Queue $queue): void
    {
        if ($queue->status !== 'waiting') {
            return;
        }

        $before = $this->positionsBeforeCreation($queue);
        $after = $this->positions();

        $this->dispatchChangedWaitingEntries($before, $after);
    }

    public function updated(Queue $queue): void
    {
        if (! $queue->wasChanged(['status', 'is_vip', 'queue_number'])) {
            return;
        }

        $before = $this->positionsBeforeUpdate($queue);
        $after = $this->positions();

        $oldStatus = (string) $queue->getRawOriginal('status');
        $newStatus = (string) $queue->status;

        if ($oldStatus === 'waiting' && $newStatus === 'serving') {
            $this->dispatchForQueue(
                queue: $queue,
                event: 'queue.turn_now',
                updateType: 'next',
                position: null,
                oldPosition: $before[(int) $queue->getKey()] ?? null,
            );
        }

        $this->dispatchChangedWaitingEntries($before, $after);
    }

    public function deleted(Queue $queue): void
    {
        $oldStatus = (string) $queue->getRawOriginal('status');
        if ($oldStatus !== 'waiting') {
            return;
        }

        $before = $this->positionsBeforeDeletion($queue);
        $after = $this->positions();

        $this->dispatchChangedWaitingEntries($before, $after);
    }

    /** @return array<int, int> queue id => one-based position */
    private function positions(): array
    {
        return Queue::query()
            ->where('status', 'waiting')
            ->orderByDesc('is_vip')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->mapWithKeys(static fn ($id, $index): array => [
                (int) $id => $index + 1,
            ])
            ->all();
    }

    /** @return array<int, int> */
    private function positionsBeforeCreation(Queue $created): array
    {
        return Queue::query()
            ->where('status', 'waiting')
            ->where('id', '!=', $created->getKey())
            ->orderByDesc('is_vip')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->mapWithKeys(static fn ($id, $index): array => [
                (int) $id => $index + 1,
            ])
            ->all();
    }

    /** @return array<int, int> */
    private function positionsBeforeUpdate(Queue $updated): array
    {
        $id = (int) $updated->getKey();
        $oldStatus = (string) $updated->getRawOriginal('status');
        $oldIsVip = (bool) $updated->getRawOriginal('is_vip');

        $rows = Queue::query()
            ->where('status', 'waiting')
            ->where('id', '!=', $id)
            ->get(['id', 'is_vip']);

        if ($oldStatus !== 'waiting') {
            return $this->sortPositions($rows);
        }

        $rows->push((object) [
            'id' => $id,
            'is_vip' => $oldIsVip,
        ]);

        return $this->sortPositions($rows);
    }

    /** @return array<int, int> */
    private function positionsBeforeDeletion(Queue $deleted): array
    {
        $id = (int) $deleted->getKey();

        $rows = Queue::query()
            ->where('status', 'waiting')
            ->where('id', '!=', $id)
            ->get(['id', 'is_vip']);

        $rows->push((object) [
            'id' => $id,
            'is_vip' => (bool) $deleted->getRawOriginal('is_vip'),
        ]);

        return $this->sortPositions($rows);
    }

    /** @return array<int, int> */
    private function sortPositions($rows): array
    {
        return $rows
            ->sortBy([
                ['is_vip', 'desc'],
                ['id', 'asc'],
            ])
            ->values()
            ->mapWithKeys(static fn ($row, $index): array => [
                (int) $row->id => $index + 1,
            ])
            ->all();
    }

    private function dispatchChangedWaitingEntries(array $before, array $after): void
    {
        $ids = array_values(array_intersect(array_keys($before), array_keys($after)));

        if ($ids === []) {
            return;
        }

        $queues = Queue::query()
            ->with(['appointment.customer'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($ids as $id) {
            $oldPosition = $before[$id] ?? null;
            $newPosition = $after[$id] ?? null;

            if ($oldPosition === null || $newPosition === null || $oldPosition === $newPosition) {
                continue;
            }

            $queue = $queues->get($id);
            if (! $queue) {
                continue;
            }

            if ($newPosition === 1) {
                $this->dispatchForQueue(
                    queue: $queue,
                    event: 'queue.almost_turn',
                    updateType: 'ready',
                    position: 1,
                    oldPosition: $oldPosition,
                );
                continue;
            }

            $this->dispatchForQueue(
                queue: $queue,
                event: 'queue.position_changed',
                updateType: 'position_update',
                position: $newPosition,
                oldPosition: $oldPosition,
            );
        }
    }

    private function dispatchForQueue(
        Queue $queue,
        string $event,
        string $updateType,
        ?int $position,
        ?int $oldPosition,
    ): void {
        $tenant = tenant();
        if (! $tenant) {
            return;
        }

        $appointment = $queue->appointment;
        if (! $appointment) {
            return;
        }

        $customer = $appointment->customer;
        if (! $customer) {
            return;
        }

        $customerId = (int) $customer->getKey();
        $name = $customer->full_name;
        $email = filled($customer->email ?? null) ? (string) $customer->email : null;
        $phone = filled($customer->phone ?? null) ? (string) $customer->phone : null;
        $locale = $customer->language ?: null;

        $tenantData = method_exists($tenant, 'getAttribute')
            ? (array) ($tenant->getAttribute('data') ?? [])
            : [];
        $locale ??= $tenantData['language'] ?? null;
        $locale ??= config('app.locale', 'ar');

        event(new QueueLifecycleNotificationRequested(
            tenantId: (string) $tenant->getKey(),
            queueId: (int) $queue->getKey(),
            appointmentId: (int) $appointment->getKey(),
            publicReference: (string) $appointment->public_reference,
            event: $event,
            updateType: $updateType,
            queueNumber: (string) $queue->queue_number,
            position: $position,
            oldPosition: $oldPosition,
            customerType: 'customer',
            customerId: $customerId,
            customerName: $name,
            email: $email,
            phone: $phone,
            locale: (string) $locale,
            eventId: (string) Str::uuid(),
        ));
    }
}
