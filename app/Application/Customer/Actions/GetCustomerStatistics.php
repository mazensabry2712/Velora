<?php

declare(strict_types=1);

namespace App\Application\Customer\Actions;

use App\Domain\Customer\Contracts\CustomerReader;

final class GetCustomerStatistics
{
    public function __construct(private readonly CustomerReader $customers) {}

    /** @return array<string, mixed> */
    public function execute(int $customerId): array
    {
        return $this->customers->getStatistics($customerId);
    }
}
