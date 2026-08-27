<?php

declare(strict_types=1);

namespace App\Application\Staff\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Models\User;
use App\Repositories\Contracts\StaffRepositoryInterface;

final class UpdateStaff
{
    public function __construct(
        private readonly StaffRepositoryInterface $staff,
        private readonly TransactionManager $transactions,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(User $member, array $data): bool
    {
        return $this->transactions->transaction(
            fn (): bool => $this->staff->update(
                $member,
                $data,
                $data['services'] ?? [],
                $data['schedule'] ?? [],
            )
        );
    }
}
