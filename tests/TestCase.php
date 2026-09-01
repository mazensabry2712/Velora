<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = (string) config(
            'tenancy.database.central_connection',
            config('database.default', 'sqlite')
        );

        $schema = Schema::connection($connection);

        if (! $schema->hasTable('migrations')) {
            Artisan::call('migrate', [
                '--database' => $connection,
                '--path' => 'database/migrations',
                '--force' => true,
                '--no-ansi' => true,
            ]);
        }
    }
}
