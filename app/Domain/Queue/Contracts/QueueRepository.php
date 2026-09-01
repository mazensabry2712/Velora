<?php

declare(strict_types=1);

namespace App\Domain\Queue\Contracts;

use App\Models\Queue;
use Illuminate\Database\Eloquent\Collection;

interface QueueRepository
{
    public function findById(int $id): ?Queue;

    /** @return Collection<int, Queue> */
    public function getByDate(string $date): Collection;

    /** @return Collection<int, Queue> */
    public function getActive(): Collection;

    public function create(array $data): Queue;

    public function update(Queue $queue, array $data): bool;

    public function delete(Queue $queue): bool;

    public function callNext(?string $date = null): ?Queue;

    public function getDailyStats(string $date): array;

    public function getOverallStats(): array;

    public function moveToNextDay(string $date, string $status): int;
}
