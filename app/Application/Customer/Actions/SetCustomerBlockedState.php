<?php

declare(strict_types=1);

namespace App\Application\Customer\Actions;

use App\Models\Customer;

final class SetCustomerBlockedState
{
    public function execute(Customer $customer, bool $blocked, ?string $reason = null): Customer
    {
        $customer->update([
            'is_blocked' => $blocked,
            'block_reason' => $blocked ? $reason : null,
        ]);

        return $customer->fresh();
    }
}
