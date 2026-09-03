<?php

declare(strict_types=1);

/*
 * PHPUnit environment bootstrap.
 *
 * The real .env file must never be committed. Some legacy tests inspect the
 * physical .env file directly, so when PHPUnit starts without a local .env we
 * create a temporary testing copy from .env.example, inject a throwaway APP_KEY,
 * and remove the generated file when the test process exits.
 *
 * SQLite uses a process-local file rather than :memory:. Laravel creates a new
 * application/connection during the test lifecycle, while :memory: creates a
 * fresh database per connection and breaks tenant/central test isolation.
 * Each PHPUnit process gets its own file, which also keeps parallel processes
 * isolated from one another.
 *
 * CI may intentionally provide a non-SQLite driver (for example MySQL). In that
 * case the bootstrap must never overwrite DB_DATABASE with a SQLite path.
 */

$root = dirname(__DIR__);
$envPath = $root . DIRECTORY_SEPARATOR . '.env';
$createdEnv = false;

if (! is_file($envPath)) {
    $examplePath = $root . DIRECTORY_SEPARATOR . '.env.example';

    if (! is_file($examplePath)) {
        throw new RuntimeException('Cannot bootstrap tests: .env.example is missing.');
    }

    $contents = file_get_contents($examplePath);

    if ($contents === false) {
        throw new RuntimeException('Cannot bootstrap tests: failed to read .env.example.');
    }

    $appKey = 'base64:' . base64_encode(random_bytes(32));

    $contents = preg_replace('/^APP_ENV=.*$/m', 'APP_ENV=testing', $contents) ?? $contents;
    $contents = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $appKey, $contents) ?? $contents;
    $contents = preg_replace('/^APP_DEBUG=.*$/m', 'APP_DEBUG=true', $contents) ?? $contents;
    $contents = preg_replace('/^APP_URL=.*$/m', 'APP_URL=http://localhost', $contents) ?? $contents;

    if (file_put_contents($envPath, $contents, LOCK_EX) === false) {
        throw new RuntimeException('Cannot bootstrap tests: failed to create temporary .env.');
    }

    $createdEnv = true;
}

$databaseConnection = strtolower(trim((string) (getenv('DB_CONNECTION') ?: 'sqlite')));
$testDatabasePath = null;

if ($databaseConnection === 'sqlite') {
    $testToken = getenv('TEST_TOKEN') ?: getenv('PARALLEL_PROCESS') ?: (string) getmypid();
    $testToken = preg_replace('/[^A-Za-z0-9_-]/', '_', $testToken) ?: (string) getmypid();
    $testDatabaseRelativePath = 'database' . DIRECTORY_SEPARATOR . 'testing_' . $testToken . '.sqlite';
    $testDatabasePath = $root . DIRECTORY_SEPARATOR . $testDatabaseRelativePath;

    if (! is_dir(dirname($testDatabasePath))) {
        mkdir(dirname($testDatabasePath), 0775, true);
    }

    if (is_file($testDatabasePath)) {
        @unlink($testDatabasePath);
    }

    if (file_put_contents($testDatabasePath, '') === false) {
        throw new RuntimeException('Cannot bootstrap tests: failed to create the SQLite test database.');
    }

    putenv('DB_DATABASE=' . $testDatabaseRelativePath);
    $_ENV['DB_DATABASE'] = $testDatabaseRelativePath;
    $_SERVER['DB_DATABASE'] = $testDatabaseRelativePath;
}

require $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

register_shutdown_function(static function () use ($envPath, $createdEnv, $testDatabasePath): void {
    if ($createdEnv && is_file($envPath)) {
        @unlink($envPath);
    }

    if ($testDatabasePath !== null && is_file($testDatabasePath)) {
        @unlink($testDatabasePath);
    }
});
