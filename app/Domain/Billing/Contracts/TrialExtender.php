<?php

declare(strict_types=1);

namespace App\Domain\Billing\Contracts;

use DateTimeInterface;

interface TrialExtender
{
    /** @return array{status: string, new_trial_ends_at?: DateTimeInterface} */
    public function extend(string|int $tenantId, int $days = 7): array;
}
