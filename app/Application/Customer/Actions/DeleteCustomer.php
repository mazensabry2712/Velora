<?php

declare(strict_types=1);

namespace App\Application\Customer\Actions;

use App\Models\Customer;

final class DeleteCustomer
{
    public function execute(Customer $customer): bool
    {
        return (bool) $customer->delete();
    }
}
