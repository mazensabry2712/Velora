<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LayerBoundaryTest extends TestCase
{
    #[Test]
    public function domain_and_application_layers_do_not_depend_on_http_or_view_layers(): void
    {
        foreach ([app_path('Domain'), app_path('Application')] as $layerPath) {
            if (! is_dir($layerPath)) {
                continue;
            }

            foreach ($this->phpFiles($layerPath) as $filePath => $contents) {
                self::assertStringNotContainsString(
                    'App\\Http\\',
                    $contents,
                    "HTTP dependency found in {$filePath}"
                );

                self::assertStringNotContainsString(
                    'Illuminate\\Http\\',
                    $contents,
                    "Illuminate HTTP dependency found in {$filePath}"
                );

                self::assertStringNotContainsString(
                    'Illuminate\\Support\\Facades\\View',
                    $contents,
                    "View facade dependency found in {$filePath}"
                );
            }
        }
    }

    #[Test]
    public function application_layer_does_not_depend_on_concrete_legacy_services_or_payment_providers(): void
    {
        $applicationPath = app_path('Application');

        if (! is_dir($applicationPath)) {
            self::markTestSkipped('Application layer is not present.');
        }

        foreach ($this->phpFiles($applicationPath) as $filePath => $contents) {
            self::assertStringNotContainsString(
                'App\\Services\\',
                $contents,
                "Legacy service dependency found in {$filePath}"
            );

            foreach (['App\\Payments\\Stripe\\', 'App\\Payments\\Moyasar\\', 'App\\Payments\\Paymob\\', 'App\\Payments\\Fawry\\'] as $providerNamespace) {
                self::assertStringNotContainsString(
                    $providerNamespace,
                    $contents,
                    "Concrete payment provider dependency found in {$filePath}"
                );
            }
        }
    }

    #[Test]
    public function domain_layer_does_not_depend_on_concrete_payment_providers_or_database_facades(): void
    {
        $domainPath = app_path('Domain');

        if (! is_dir($domainPath)) {
            self::markTestSkipped('Domain layer is not present.');
        }

        foreach ($this->phpFiles($domainPath) as $filePath => $contents) {
            self::assertStringNotContainsString(
                'Illuminate\\Support\\Facades\\DB',
                $contents,
                "Database facade dependency found in {$filePath}"
            );

            foreach (['Stripe', 'Moyasar', 'Paymob', 'Fawry'] as $provider) {
                self::assertStringNotContainsString(
                    "App\\Payments\\{$provider}",
                    $contents,
                    "Concrete {$provider} dependency found in {$filePath}"
                );
            }
        }
    }

    #[Test]
    public function domain_layer_does_not_depend_on_framework_infrastructure_or_legacy_service_classes(): void
    {
        $domainPath = app_path('Domain');

        if (! is_dir($domainPath)) {
            self::markTestSkipped('Domain layer is not present.');
        }

        foreach ($this->phpFiles($domainPath) as $filePath => $contents) {
            foreach ([
                'App\\Infrastructure\\',
                'App\\Services\\',
                'App\\Repositories\\',
            ] as $forbiddenNamespace) {
                self::assertStringNotContainsString(
                    $forbiddenNamespace,
                    $contents,
                    "Forbidden infrastructure dependency found in {$filePath}"
                );
            }
        }
    }

    #[Test]
    public function application_layer_depends_on_abstractions_not_infrastructure_implementations(): void
    {
        $applicationPath = app_path('Application');

        if (! is_dir($applicationPath)) {
            self::markTestSkipped('Application layer is not present.');
        }

        foreach ($this->phpFiles($applicationPath) as $filePath => $contents) {
            self::assertStringNotContainsString(
                'App\\Infrastructure\\',
                $contents,
                "Infrastructure implementation dependency found in {$filePath}"
            );

            self::assertStringNotContainsString(
                'App\\Repositories\\',
                $contents,
                "Repository implementation dependency found in {$filePath}"
            );
        }
    }

    /** @return array<string, string> */
    private function phpFiles(string $root): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            self::assertIsString($contents);
            $files[$file->getPathname()] = $contents;
        }

        return $files;
    }
}
