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
        // HTTP middleware can change either mutable singleton/session state,
        // so leaving it behind makes later tests order-dependent when they
        // read localized model accessors outside a request.
        App::setLocale((string) config('app.locale', 'en'));
        session()->forget('locale');

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
