<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('qa')]
#[Group('foundation')]
final class EnvironmentFoundationScenarioTest extends TestCase
{
    #[Test]
    public function central_database_has_the_required_platform_tables(): void
    {
        $required = [
            'tenants',
            'domains',
            'subscription_plans',
            'tenant_subscriptions',
            'activity_logs',
            'upgrade_requests',
            'webhook_events',
        ];

        foreach ($required as $table) {
            $this->assertTrue(
                Schema::connection(config('database.default'))->hasTable($table),
                "Required central table [{$table}] is missing."
            );
        }
    }

    #[Test]
    public function migrations_are_idempotent_in_a_clean_environment(): void
    {
        $this->assertSame(0, Artisan::call('migrate:status', ['--no-ansi' => true]));
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true, '--no-ansi' => true]));

        $pending = trim(Artisan::output());
        $this->assertStringNotContainsString('Pending', $pending);
    }

    #[Test]
    public function configured_central_connection_is_reachable(): void
    {
        $connection = (string) config('tenancy.database.central_connection', config('database.default'));

        $this->assertNotSame('', $connection);
        $this->assertSame(1, DB::connection($connection)->selectOne('SELECT 1 AS healthy')->healthy);
    }
}
