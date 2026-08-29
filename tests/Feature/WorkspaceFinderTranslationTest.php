<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class WorkspaceFinderTranslationTest extends TestCase
{
    public function test_workspace_finder_has_complete_localized_copy_for_every_supported_locale(): void
    {
        $translations = require base_path('lang/workspace.php');
        $supportedLocales = array_keys(config('locales.languages', []));

        self::assertNotEmpty($supportedLocales);

        $requiredKeys = [
            'title',
            'subtitle',
            'label',
            'placeholder',
            'button',
            'no_account',
            'checking',
            'found',
            'not_found',
            'verify_error',
            'invalid',
        ];

        foreach ($supportedLocales as $locale) {
            self::assertArrayHasKey($locale, $translations, "Missing workspace translation locale [{$locale}].");

            foreach ($requiredKeys as $key) {
                self::assertArrayHasKey($key, $translations[$locale], "Missing workspace translation key [{$key}] for locale [{$locale}].");
                self::assertNotSame('', trim((string) $translations[$locale][$key]), "Empty workspace translation key [{$key}] for locale [{$locale}].");
            }

            self::assertStringNotContainsString(
                'Enter your email',
                (string) $translations[$locale]['subtitle'],
                "Workspace finder locale [{$locale}] still contains the obsolete email copy."
            );
            self::assertStringNotContainsString(
                'Email address',
                (string) $translations[$locale]['label'],
                "Workspace finder locale [{$locale}] still uses an email label."
            );
        }
    }
}
