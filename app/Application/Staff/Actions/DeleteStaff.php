<?php

declare(strict_types=1);

namespace App\Application\Staff\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Models\User;
use App\Repositories\Contracts\StaffRepositoryInterface;

final class DeleteStaff
{
    public function __construct(
        private readonly StaffRepositoryInterface $staff,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(User $member): bool
    {
        return $this->transactions->transaction(
            fn (): bool => $this->staff->delete($member)
        );
    }
}
