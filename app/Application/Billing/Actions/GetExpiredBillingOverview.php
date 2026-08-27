<?php

declare(strict_types=1);

namespace App\Application\Billing\Actions;

use App\Domain\Billing\Contracts\BillingReader;

final class GetExpiredBillingOverview
{
    public function __construct(private readonly BillingReader $reader) {}

    /** @return array{subscription: object|null, plans: iterable, invoices: iterable} */
    public function execute(string|int $tenantId): array
    {
        return $this->reader->expiredOverview($tenantId);
    }
}
