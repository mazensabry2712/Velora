<?php

declare(strict_types=1);

namespace App\Application\Customer\Actions;

use App\Models\Customer;

final class CreateCustomer
{
    /** @param array<string, mixed> $data */
    public function execute(array $data): Customer
    {
        return Customer::create($data);
    }
}
