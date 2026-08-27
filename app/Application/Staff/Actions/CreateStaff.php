<?php

declare(strict_types=1);

namespace App\Application\Staff\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Models\User;

final class CreateStaff
{
    public function __construct(
        private readonly StaffRepositoryInterface $staff,
        private readonly TransactionManager $transactions,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data): User
    {
        return $this->transactions->transaction(
            fn (): User => $this->staff->create(
                $data,
                $data['services'] ?? [],
                $data['schedule'] ?? [],
            )
        );
    }
}
