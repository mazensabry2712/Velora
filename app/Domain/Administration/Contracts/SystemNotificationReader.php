<?php

declare(strict_types=1);

namespace App\Domain\Administration\Contracts;

use Illuminate\Support\Collection;

interface SystemNotificationReader
{
    /** @return Collection<int, object> */
    public function forTenant(string $tenantId, int $limit = 5): Collection;
}
