<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing;

use App\Domain\Subscription\Contracts\UpgradeRequestWriter;
use Illuminate\Support\Facades\DB;

final class EloquentUpgradeRequestWriter implements UpgradeRequestWriter
{
    public function findActivePlan(int $planId): ?object
    {
        return DB::connection('mysql')->table('subscription_plans')
            ->where('id', $planId)
            ->where('is_active', true)
            ->first();
    }

    public function create(array $data): void
    {
        DB::connection('mysql')->table('upgrade_requests')->insert($data);
    }
}
