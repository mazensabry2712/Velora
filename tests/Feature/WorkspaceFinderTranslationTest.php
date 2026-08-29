<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class WorkspaceFinderTranslationTest extends TestCase
{
    public function test_workspace_finder_has_complete_localized_copy_for_every_supported_locale(): void
    {
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
            $landing = require base_path("lang/{$locale}/landing.php");
            self::assertArrayHasKey('workspace_finder', $landing, "Missing landing workspace translations for locale [{$locale}].");
            self::assertArrayHasKey($locale, $landing['workspace_finder'], "Missing workspace translation locale [{$locale}].");

            foreach ($requiredKeys as $key) {
                self::assertArrayHasKey($key, $landing['workspace_finder'][$locale], "Missing workspace translation key [{$key}] for locale [{$locale}].");
                self::assertNotSame('', trim((string) $landing['workspace_finder'][$locale][$key]), "Empty workspace translation key [{$key}] for locale [{$locale}].");
            }

            self::assertStringNotContainsString(
                'Enter your email',
                (string) $landing['workspace_finder'][$locale]['subtitle'],
                "Workspace finder locale [{$locale}] still contains the obsolete email copy."
            );
            self::assertStringNotContainsString(
                'Email address',
                (string) $landing['workspace_finder'][$locale]['label'],
                "Workspace finder locale [{$locale}] still uses an email label."
            );
        }
    }
}
