<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Domain\Queue\Contracts\QueueReader;

final class GetQueueOverview
{
    public function __construct(private readonly QueueReader $queues) {}

    public function execute(string $date, ?string $status = null): array
    {
        $queues = $this->queues->forDate($date, $status);

        return [
            'total' => $queues->count(),
            'waiting' => $queues->where('status', 'waiting')->count(),
            'current' => $queues->where('status', 'serving')->first(),
            'queues' => $queues,
        ];
    }
}
