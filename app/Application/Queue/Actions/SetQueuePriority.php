<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Domain\Queue\Contracts\QueueRepository;
use App\Models\Queue;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class SetQueuePriority
{
    public function __construct(private readonly QueueRepository $queues) {}

    public function execute(int $queueId, bool $isVip): Queue
    {
        $queue = $this->queues->findById($queueId);

        if (! $queue) {
            throw ValidationException::withMessages(['queue_id' => ['Queue not found.']]);
        }

        if ($queue->status !== 'waiting') {
            throw ValidationException::withMessages(['queue_id' => ['Can only change priority for waiting queues.']]);
        }

        if (! $this->queues->update($queue, ['is_vip' => $isVip])) {
            throw new RuntimeException('Failed to update queue priority.');
        }

        $queue->is_vip = $isVip;

        return $queue;
    }
}
