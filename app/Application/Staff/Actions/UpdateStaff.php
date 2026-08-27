<?php

declare(strict_types=1);

namespace App\Application\Staff\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Domain\Staff\Contracts\StaffWriter;
use App\Models\User;

final class UpdateStaff
{
    public function __construct(
        private readonly StaffWriter $staff,
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
