<?php

declare(strict_types=1);

namespace App\Application\Customer\Actions;

use App\Domain\Customer\Contracts\CustomerReader;
use App\Models\Customer;

final class GetCustomer
{
    public function __construct(private readonly CustomerReader $customers) {}

    public function execute(int $customerId): Customer
    {
        return $this->customers->findWithStats($customerId);
    }
}
