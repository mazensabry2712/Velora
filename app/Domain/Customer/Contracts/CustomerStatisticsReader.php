<?php

declare(strict_types=1);

namespace App\Domain\Customer\Contracts;

interface CustomerStatisticsReader
{
    /** @return array<string, mixed> */
    public function getStatistics(int $customerId): array;
}
