<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Domain\Queue\Contracts\QueueReader;
use App\Models\User;

final class GetCustomerQueue
{
    public function __construct(private readonly QueueReader $queues) {}

    public function execute(User $user, string $date): array
    {
        return $this->queues->forCustomer($user, $date);
    }
}
