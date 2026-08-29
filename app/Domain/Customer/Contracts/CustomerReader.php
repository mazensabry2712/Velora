<?php

declare(strict_types=1);

namespace App\Domain\Customer\Contracts;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CustomerReader
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function findWithStats(int $customerId): Customer;

    /** @return array<string, mixed> */
    public function getStatistics(int $customerId): array;

    public function paginateAppointments(int $customerId, int $perPage = 15): LengthAwarePaginator;
}
