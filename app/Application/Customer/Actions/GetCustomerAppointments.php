<?php

declare(strict_types=1);

namespace App\Application\Customer\Actions;

use App\Domain\Customer\Contracts\CustomerReader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GetCustomerAppointments
{
    public function __construct(private readonly CustomerReader $customers) {}

    public function execute(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->customers->paginateAppointments($customerId, $perPage);
    }
}
