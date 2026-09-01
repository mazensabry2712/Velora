<?php

declare(strict_types=1);

/*
 * PHPUnit environment bootstrap.
 *
 * The real .env file must never be committed. Some legacy tests inspect the
 * physical .env file directly, so when PHPUnit starts without a local .env we
 * create a temporary testing copy from .env.example, inject a throwaway APP_KEY,
 * and remove the generated file when the test process exits.
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

require $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if ($createdEnv) {
    register_shutdown_function(static function () use ($envPath): void {
        if (is_file($envPath)) {
            @unlink($envPath);
        }
    });
}
