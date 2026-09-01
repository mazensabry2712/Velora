<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\StorageDriver;
use Stancl\Tenancy\Contracts\TenantDatabaseManager;

final class PermanentlyDeleteExpiredTenants extends Command
{
    protected $signature = 'subscriptions:purge-expired {--force : Skip the interactive confirmation prompt}';

    protected $description = 'Permanently delete tenants whose locked period has expired';

    private function centralConnection(): string
    {
        return (string) config('tenancy.database.central_connection', config('database.default', 'mysql'));
    }

    public function handle(): int
    {
        $now = now();
        $centralConnection = $this->centralConnection();

        $subscriptions = DB::connection($centralConnection)
            ->table('tenant_subscriptions')
            ->where('status', 'locked')
            ->whereNotNull('deletion_at')
            ->where('deletion_at', '<=', $now)
            ->orderBy('id')
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No tenants are due for permanent deletion.');
            return self::SUCCESS;
        }

        $this->warn("{$subscriptions->count()} tenant(s) are due for permanent deletion.");

        if (! $this->option('force') && ! $this->confirm('Permanently delete their databases, files, domains and central records?')) {
            $this->info('Deletion cancelled.');
            return self::SUCCESS;
        }

        $deleted = 0;
        $centralTenants = Tenant::on($centralConnection);

        foreach ($subscriptions as $subscription) {
            $tenantId = (string) $subscription->tenant_id;
            $tenant = $centralTenants->newQuery()->withTrashed()->find($tenantId);

            if (! $tenant) {
                DB::connection($centralConnection)
                    ->table('tenant_subscriptions')
                    ->where('id', $subscription->id)
                    ->delete();
                continue;
            }

            try {
                // Delete tenant-owned uploaded files from the configured tenancy storage first.
                app(StorageDriver::class)->deleteTenant($tenant);

                // Drop the isolated tenant database so no tenant data remains on the DB server.
                app(TenantDatabaseManager::class)->deleteDatabase($tenant);

                // Remove central records after tenant resources are gone.
                DB::connection($centralConnection)
                    ->table('tenant_subscriptions')
                    ->where('tenant_id', $tenantId)
                    ->delete();
                $tenant->domains()->delete();
                $tenant->forceDelete();

                $deleted++;
                $this->line("  [deleted] Tenant: {$tenantId}");
                Log::info('Tenant permanently deleted after subscription lifecycle.', [
                    'tenant_id' => $tenantId,
                    'subscription_id' => $subscription->id,
                    'deletion_at' => $subscription->deletion_at,
                ]);
            } catch (\Throwable $e) {
                $this->error("  [failed] Tenant {$tenantId}: {$e->getMessage()}");
                Log::error('Permanent tenant deletion failed; tenant retained for retry.', [
                    'tenant_id' => $tenantId,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Permanent deletion complete. Deleted {$deleted} tenant(s).");
        return self::SUCCESS;
    }
}
