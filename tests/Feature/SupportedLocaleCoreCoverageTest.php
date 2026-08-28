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
            foreach (['auth.php', 'booking.php', 'messages.php', 'pagination.php', 'passwords.php', 'validation.php', 'notifications.php'] as $file) {
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

    #[Test]
    public function notification_translation_keys_and_placeholders_match_the_english_catalog(): void
    {
        $locales = array_values(array_unique(config('localizer.supported_locales', [])));
        $english = require base_path('lang/en/notifications.php');

        $flatten = static function (array $values, string $prefix = '') use (&$flatten): array {
            $result = [];

            foreach ($values as $key => $value) {
                $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

                if (is_array($value)) {
                    $result += $flatten($value, $path);
                } else {
                    $result[$path] = (string) $value;
                }
            }

            return $result;
        };

        $englishFlat = $flatten($english);

        foreach ($locales as $locale) {
            $translated = require base_path("lang/{$locale}/notifications.php");
            $translatedFlat = $flatten($translated);

            self::assertSame(
                array_keys($englishFlat),
                array_keys($translatedFlat),
                "Notification key mismatch for supported locale [{$locale}]."
            );

            foreach ($englishFlat as $key => $englishValue) {
                preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/', $englishValue, $englishPlaceholders);
                preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/', $translatedFlat[$key], $translatedPlaceholders);

                $expected = array_values(array_unique($englishPlaceholders[0]));
                $actual = array_values(array_unique($translatedPlaceholders[0]));
                sort($expected);
                sort($actual);

                self::assertSame(
                    $expected,
                    $actual,
                    "Notification placeholder mismatch for [{$locale}] key [{$key}]."
                );
            }
        }
    }
}
