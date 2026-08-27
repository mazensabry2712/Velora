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

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($layerPath, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());
                self::assertIsString($contents);

                self::assertStringNotContainsString(
                    'App\\Http\\',
                    $contents,
                    "HTTP dependency found in {$file->getPathname()}"
                );

                self::assertStringNotContainsString(
                    'Illuminate\\Http\\',
                    $contents,
                    "Illuminate HTTP dependency found in {$file->getPathname()}"
                );

                self::assertStringNotContainsString(
                    'Illuminate\\Support\\Facades\\View',
                    $contents,
                    "View facade dependency found in {$file->getPathname()}"
                );
            }
        }
    }

    #[Test]
    public function domain_layer_does_not_depend_on_concrete_payment_providers(): void
    {
        $domainPath = app_path('Domain');

        if (! is_dir($domainPath)) {
            self::markTestSkipped('Domain layer is not present.');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($domainPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            self::assertIsString($contents);

            foreach (['Stripe', 'Moyasar', 'Paymob', 'Fawry'] as $provider) {
                self::assertStringNotContainsString(
                    "App\\Payments\\{$provider}",
                    $contents,
                    "Concrete {$provider} dependency found in {$file->getPathname()}"
                );
            }
        }
    }
}
