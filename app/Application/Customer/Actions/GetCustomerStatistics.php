<?php

declare(strict_types=1);

namespace App\Application\Customer\Actions;

use App\Domain\Customer\Contracts\CustomerStatisticsReader;

final class GetCustomerStatistics
{
    public function __construct(private readonly CustomerStatisticsReader $statistics) {}

    /** @return array<string, mixed> */
    public function execute(int $customerId): array
    {
        return $this->statistics->getStatistics($customerId);
    }
}
