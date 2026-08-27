<?php

declare(strict_types=1);

namespace App\Application\Customer\Actions;

use App\Domain\Customer\Contracts\CustomerReader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GetCustomers
{
    public function __construct(private readonly CustomerReader $customers) {}

    /** @param array<string, mixed> $filters */
    public function execute(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->customers->paginate($filters, $perPage);
    }
}
