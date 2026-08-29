<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Lang;
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

        $originalLocale = app()->getLocale();

        try {
            foreach ($supportedLocales as $locale) {
                app()->setLocale($locale);

                // Resolve the translation through Laravel so locale files that
                // intentionally inherit the canonical landing dictionary are
                // validated the same way the actual view resolves them.
                $workspace = Lang::get("landing.workspace_finder.{$locale}");

                self::assertIsArray(
                    $workspace,
                    "Missing resolved landing workspace translations for locale [{$locale}]."
                );

                foreach ($requiredKeys as $key) {
                    self::assertArrayHasKey(
                        $key,
                        $workspace,
                        "Missing workspace translation key [{$key}] for locale [{$locale}]."
                    );
                    self::assertNotSame(
                        '',
                        trim((string) $workspace[$key]),
                        "Empty workspace translation key [{$key}] for locale [{$locale}]."
                    );
                }

                self::assertStringNotContainsString(
                    'Enter your email',
                    (string) $workspace['subtitle'],
                    "Workspace finder locale [{$locale}] still contains the obsolete email copy."
                );
                self::assertStringNotContainsString(
                    'Email address',
                    (string) $workspace['label'],
                    "Workspace finder locale [{$locale}] still uses an email label."
                );
            }
        } finally {
            app()->setLocale($originalLocale);
        }
    }
}
