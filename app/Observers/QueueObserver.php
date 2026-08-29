<?php

declare(strict_types=1);

namespace App\Observers;

use App\Domain\Queue\Events\QueueLifecycleNotificationRequested;
use App\Models\Customer;
use App\Models\Queue;
use App\Support\TenantContext;
use Illuminate\Support\Str;

final class QueueObserver
{
    /** @var array<int, array<int, int>> */
    private array $beforePositions = [];

    public function creating(Queue $queue): void
    {
        if ($queue->status !== 'waiting') {
            return;
        }

        $this->beforePositions[spl_object_id($queue)] = $this->positions();
    }

    public function created(Queue $queue): void
    {
        if ($queue->status !== 'waiting') {
            return;
        }

        $before = $this->beforePositions[spl_object_id($queue)] ?? [];
        $after = $this->positions();

        $this->dispatchChangedWaitingEntries($before, $after);
        unset($this->beforePositions[spl_object_id($queue)]);
    }

    public function updating(Queue $queue): void
    {
        if (! $queue->isDirty(['status', 'is_vip', 'queue_number'])) {
            return;
        }

        $this->beforePositions[spl_object_id($queue)] = $this->positions();
    }

    public function updated(Queue $queue): void
    {
        if (! $queue->wasChanged(['status', 'is_vip', 'queue_number'])) {
            return;
        }

        $before = $this->beforePositions[spl_object_id($queue)] ?? [];
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
        unset($this->beforePositions[spl_object_id($queue)]);
    }

    public function deleting(Queue $queue): void
    {
        if ($queue->status !== 'waiting') {
            return;
        }

        $this->beforePositions[spl_object_id($queue)] = $this->positions();
    }

    public function deleted(Queue $queue): void
    {
        $before = $this->beforePositions[spl_object_id($queue)] ?? [];
        $after = $this->positions();

        $this->dispatchChangedWaitingEntries($before, $after);
        unset($this->beforePositions[spl_object_id($queue)]);
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

    /**
     * @param array<int, int> $before
     * @param array<int, int> $after
     */
    private function dispatchChangedWaitingEntries(array $before, array $after): void
    {
        $ids = array_values(array_intersect(array_keys($before), array_keys($after)));

        if ($ids === []) {
            return;
        }

        $queues = Queue::query()
            ->with(['appointment.customer', 'appointment.newCustomer'])
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

        $customer = $appointment->customer ?: $appointment->newCustomer;
        if (! $customer) {
            return;
        }

        $customerType = $customer instanceof Customer ? 'customer' : 'user';
        $customerId = (int) $customer->getKey();
        $name = $customer instanceof Customer
            ? $customer->full_name
            : (string) $customer->name;
        $email = filled($customer->email ?? null) ? (string) $customer->email : null;
        $phone = filled($customer->phone ?? null) ? (string) $customer->phone : null;

        $locale = $customer instanceof Customer
            ? ($customer->language ?: null)
            : ($customer->locale ?: null);

        // Do not query Tenant->settings here: queue lifecycle observers also run in
        // lightweight tenant tests where that optional relation/table is absent.
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
            customerType: $customerType,
            customerId: $customerId,
            customerName: $name,
            email: $email,
            phone: $phone,
            locale: (string) $locale,
            eventId: (string) Str::uuid(),
        ));
    }
}
