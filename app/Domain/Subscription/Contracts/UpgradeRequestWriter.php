<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Contracts;

interface UpgradeRequestWriter
{
    /** @return object|null */
    public function findActivePlan(int $planId): ?object;

    /** @param array<string, mixed> $data */
    public function create(array $data): void;
}
