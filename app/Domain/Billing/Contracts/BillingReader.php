<?php

declare(strict_types=1);

namespace App\Domain\Billing\Contracts;

interface BillingReader
{
    /** @return array{subscription: object|null, plans: iterable, invoices: iterable} */
    public function expiredOverview(string|int $tenantId): array;

    public function stripeCustomerId(string|int $tenantId): ?string;
}
