<?php

declare(strict_types=1);

namespace App\Infrastructure\Administration;

use App\Domain\Administration\Contracts\SystemNotificationReader;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class LegacySystemNotificationReader implements SystemNotificationReader
{
    private function centralConnection(): string
    {
        return (string) config('tenancy.database.central_connection', config('database.default', 'mysql'));
    }

    public function forTenant(string $tenantId, int $limit = 5): Collection
    {
        return DB::connection($this->centralConnection())
            ->table('system_notifications')
            ->where('is_sent', true)
            ->where(function ($query) use ($tenantId) {
                $query->where('target', 'all')
                    ->orWhere(function ($specific) use ($tenantId) {
                        $specific->where('target', 'specific')
                            ->whereJsonContains('tenant_ids', $tenantId);
                    });
            })
            ->where('sent_at', '>=', now()->subDays(7))
            ->orderByDesc('sent_at')
            ->limit(max(1, $limit))
            ->get(['id', 'title', 'message', 'type', 'sent_at']);
    }
}
