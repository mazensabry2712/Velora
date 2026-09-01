<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Stancl\Tenancy\Contracts\StorageDriver;
use Stancl\Tenancy\Contracts\TenantDatabaseManager;
use Tests\TestCase;

#[Group('qa')]
#[Group('master-scenario')]
#[Group('deletion')]
final class TenantDeletionSafetyScenarioTest extends TestCase
{
    #[Test]
    public function failed_resource_cleanup_retains_tenant_and_subscription_for_retry(): void
    {
        $tenantId = 'qa-delete-failure-' . bin2hex(random_bytes(4));
        $planId = SubscriptionPlan::query()->value('id');
        $this->assertNotNull($planId);

        Tenant::create([
            'id' => $tenantId,
            'name' => 'QA Delete Failure',
        ]);

        TenantSubscription::query()->insert([
            'tenant_id' => $tenantId,
            'subscription_plan_id' => $planId,
            'status' => 'locked',
            'deletion_at' => now()->subMinute(),
            'created_at' => now()->subDays(30),
            'updated_at' => now(),
        ]);

        $storage = Mockery::mock(StorageDriver::class);
        $storage->shouldReceive('deleteTenant')
            ->once()
            ->andThrow(new \RuntimeException('storage cleanup failed'));
        app()->instance(StorageDriver::class, $storage);

        try {
            Artisan::call('subscriptions:purge-expired', ['--force' => true]);

            $this->assertNotNull(Tenant::withTrashed()->find($tenantId));
            $this->assertDatabaseHas('tenant_subscriptions', ['tenant_id' => $tenantId, 'status' => 'locked']);
        } finally {
            TenantSubscription::query()->where('tenant_id', $tenantId)->delete();
            Tenant::withTrashed()->whereKey($tenantId)->forceDelete();
        }
    }

    #[Test]
    public function successful_resource_cleanup_removes_tenant_subscription_and_tenant_record(): void
    {
        $tenantId = 'qa-delete-success-' . bin2hex(random_bytes(4));
        $planId = SubscriptionPlan::query()->value('id');
        $this->assertNotNull($planId);

        Tenant::create([
            'id' => $tenantId,
            'name' => 'QA Delete Success',
        ]);

        TenantSubscription::query()->insert([
            'tenant_id' => $tenantId,
            'subscription_plan_id' => $planId,
            'status' => 'locked',
            'deletion_at' => now()->subMinute(),
            'created_at' => now()->subDays(30),
            'updated_at' => now(),
        ]);

        $storage = Mockery::mock(StorageDriver::class);
        $storage->shouldReceive('deleteTenant')->once()->andReturnNull();
        app()->instance(StorageDriver::class, $storage);

        $databaseManager = Mockery::mock(TenantDatabaseManager::class);
        $databaseManager->shouldReceive('deleteDatabase')->once()->andReturnNull();
        app()->instance(TenantDatabaseManager::class, $databaseManager);

        Artisan::call('subscriptions:purge-expired', ['--force' => true]);

        $this->assertDatabaseMissing('tenant_subscriptions', ['tenant_id' => $tenantId]);
        $this->assertNull(Tenant::withTrashed()->find($tenantId));
    }
}
