<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing;

use App\Domain\Subscription\Contracts\UpgradeRequestWriter;
use Illuminate\Support\Facades\DB;

final class EloquentUpgradeRequestWriter implements UpgradeRequestWriter
{
    private function centralConnection(): string
    {
        return (string) config('tenancy.database.central_connection', config('database.default', 'mysql'));
    }

    public function findActivePlan(int $planId): ?object
    {
        return DB::connection($this->centralConnection())->table('subscription_plans')
            ->where('id', $planId)
            ->where('is_active', true)
            ->first();
    }

    public function create(array $data): void
    {
        DB::connection($this->centralConnection())->table('upgrade_requests')->insert($data);
    }
}
