<?php

declare(strict_types=1);

namespace App\Domain\Subscription\Contracts;

interface SubscriptionReader
{
    /** @return array<string, mixed>|null */
    public function current(): ?array;

    /** @return array<string, mixed> */
    public function usage(): array;

    /** @return array<string, mixed> */
    public function availableUpgrades(): array;

    /** @return array<int, mixed> */
    public function invoices(int $limit = 20): array;

    /** @return array<string, mixed> */
    public function checkLimit(string $action): array;

    public function hasFeature(string $feature): bool;
}
