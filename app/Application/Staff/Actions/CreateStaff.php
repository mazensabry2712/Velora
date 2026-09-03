<?php

declare(strict_types=1);

namespace App\Application\Staff\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Domain\Staff\Contracts\StaffWriter;
use App\Models\Staff;

final class CreateStaff
{
    public function __construct(
        private readonly StaffWriter $staff,
        private readonly TransactionManager $transactions,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data): Staff
    {
        return $this->transactions->transaction(
            fn (): Staff => $this->staff->create(
                $data,
                $data['services'] ?? [],
                $data['schedule'] ?? [],
            )
        );
    }
}
