<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Domain\Queue\Contracts\QueueRepository;
use App\Jobs\SendQueueNotification;
use App\Models\Appointment;
use App\Models\Queue;
use Illuminate\Validation\ValidationException;

final class SkipQueueEntry
{
    public function __construct(
        private readonly QueueRepository $queues,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(int $queueId): Queue
    {
        return $this->transactions->transaction(function () use ($queueId): Queue {
            $queue = $this->queues->findById($queueId);

            if (! $queue) {
                throw ValidationException::withMessages(['id' => ['Queue entry not found.']]);
            }

            if ($queue->status !== 'waiting') {
                throw ValidationException::withMessages(['id' => ['Can only skip waiting queues.']]);
            }

            $this->queues->update($queue, ['status' => 'cancelled']);
            $queue->loadMissing('appointment.customer');
            $queue->appointment?->update(['status' => Appointment::STATUS_CANCELLED]);

            $customer = $queue->appointment?->customer;
            if ($customer) {
                $locale = tenant()?->settings?->language ?? 'en';
                SendQueueNotification::dispatch($queue->refresh(), $customer, 'cancelled', $locale);
            }

            return $queue->refresh();
        });
    }
}
