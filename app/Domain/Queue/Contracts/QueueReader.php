<?php

declare(strict_types=1);

namespace App\Domain\Queue\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface QueueReader
{
    /** @return Collection<int, \App\Models\Queue> */
    public function forDate(string $date, ?string $status = null): Collection;

    /** @return array{queue: ?\App\Models\Queue, position: int, estimated_wait_time: int, is_vip: bool} */
    public function forCustomer(User $user, string $date): array;

    /** @return array<string, mixed> */
    public function status(string $queueNumber, string $date): array;
}
