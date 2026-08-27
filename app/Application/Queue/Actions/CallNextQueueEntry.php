<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Domain\Queue\Contracts\QueueRepository;
use App\Models\Queue;

final class CallNextQueueEntry
{
    public function __construct(
        private readonly QueueRepository $queues,
    ) {}

    public function execute(): ?Queue
    {
        return $this->queues->callNext();
    }
}
