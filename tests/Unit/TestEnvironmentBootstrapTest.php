<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class TestEnvironmentBootstrapTest extends TestCase
{
    public function test_phpunit_bootstrap_provides_a_local_environment_with_an_application_key(): void
    {
        $root = dirname(__DIR__, 2);
        $envPath = $root . DIRECTORY_SEPARATOR . '.env';

        $this->assertFileExists($envPath);

        $contents = file_get_contents($envPath);

        $this->assertIsString($contents);
        $this->assertStringContainsString('APP_ENV=testing', $contents);
        $this->assertMatchesRegularExpression('/^APP_KEY=base64:[A-Za-z0-9+\/=]+$/m', $contents);
    }

    public function test_phpunit_bootstrap_uses_a_process_isolated_sqlite_database(): void
    {
        $database = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? '');

        $this->assertStringStartsWith('database' . DIRECTORY_SEPARATOR . 'testing_', $database);
        $this->assertStringEndsWith('.sqlite', $database);
        $this->assertFileExists(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $database);
    }

    public function test_repository_does_not_track_the_local_environment_or_test_database_files(): void
    {
        $root = dirname(__DIR__, 2);
        $gitignore = file_get_contents($root . DIRECTORY_SEPARATOR . '.gitignore');

        $this->assertIsString($gitignore);
        $this->assertStringContainsString('.env', $gitignore);
        $this->assertStringContainsString('!.env.example', $gitignore);
        $this->assertStringContainsString('database/testing_*.sqlite', $gitignore);
    }
}
