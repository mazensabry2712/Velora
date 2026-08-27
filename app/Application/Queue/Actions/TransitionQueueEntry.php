<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Domain\Queue\Contracts\QueueRepository;
use App\Models\Queue;
use InvalidArgumentException;

final class TransitionQueueEntry
{
    private const ALLOWED = [
        'waiting' => ['serving'],
        'serving' => ['waiting', 'completed'],
        'completed' => [],
    ];

    public function __construct(
        private readonly QueueRepository $queues,
    ) {}

    public function execute(Queue $queue, string $targetStatus): Queue
    {
        $current = (string) $queue->status;
        $targetStatus = strtolower(trim($targetStatus));

        if (! array_key_exists($targetStatus, self::ALLOWED)) {
            throw new InvalidArgumentException('Unsupported queue status.');
        }

        if ($current === $targetStatus) {
            return $queue;
        }

        if (! in_array($targetStatus, self::ALLOWED[$current] ?? [], true)) {
            throw new InvalidArgumentException("Invalid queue transition: {$current} -> {$targetStatus}.");
        }

        $this->queues->update($queue, ['status' => $targetStatus]);

        return $queue->refresh();
    }
}
