<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SupportedLocaleCoreCoverageTest extends TestCase
{
    #[Test]
    public function every_supported_locale_has_core_tenant_translation_bundles(): void
    {
        $locales = array_values(array_unique(config('localizer.supported_locales', [])));

        self::assertNotEmpty($locales);

        foreach ($locales as $locale) {
            foreach (['auth.php', 'booking.php'] as $file) {
                $path = base_path("lang/{$locale}/{$file}");

                self::assertFileExists(
                    $path,
                    "Missing {$file} translation bundle for supported locale [{$locale}]."
                );
            }
        }
    }

    #[Test]
    public function every_supported_locale_declares_a_text_direction(): void
    {
        $locales = array_values(array_unique(config('localizer.supported_locales', [])));
        $directions = config('localizer.locale_directions', []);

        foreach ($locales as $locale) {
            self::assertContains(
                $directions[$locale] ?? null,
                ['ltr', 'rtl'],
                "Missing or invalid text direction for supported locale [{$locale}]."
            );
        }
    }
}
