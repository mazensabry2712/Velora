<?php

declare(strict_types=1);

namespace App\Application\Billing\Actions;

use App\Domain\Billing\Contracts\TrialExtender;

final class ExtendTrial
{
    public function __construct(private readonly TrialExtender $extender) {}

    /** @return array{status: string, new_trial_ends_at?: \DateTimeInterface} */
    public function execute(string|int $tenantId, int $days = 7): array
    {
        return $this->extender->extend($tenantId, $days);
    }
}
