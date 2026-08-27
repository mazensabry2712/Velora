<?php

declare(strict_types=1);

namespace App\Application\Staff\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Domain\Staff\Contracts\StaffWriter;
use App\Models\User;

final class DeleteStaff
{
    public function __construct(
        private readonly StaffWriter $staff,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(User $member): bool
    {
        return $this->transactions->transaction(
            fn (): bool => $this->staff->delete($member)
        );
    }
}
