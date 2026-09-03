<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Every test gets a clean application locale and session locale.
        App::setLocale((string) config('app.locale', 'en'));
        session()->forget('locale');

        $connection = (string) config(
            'tenancy.database.central_connection',
            config('database.default', 'mysql')
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
