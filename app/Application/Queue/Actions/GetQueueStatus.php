<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Domain\Queue\Contracts\QueueReader;

final class GetQueueStatus
{
    public function __construct(private readonly QueueReader $queues) {}

    public function execute(string $queueNumber, string $date): array
    {
        return $this->queues->status($queueNumber, $date);
    }
}
