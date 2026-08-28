<?php

namespace Tests\Feature;

use Tests\TestCase;

class VerificationLocaleCoverageTest extends TestCase
{
    public function test_every_supported_locale_has_a_complete_verification_bundle(): void
    {
        $supported = config('localizer.supported_locales', []);
        $required = ['title', 'email_verified', 'message', 'business'];

        foreach ($supported as $locale) {
            $path = base_path("lang/{$locale}/verification.php");

            $this->assertFileExists($path, "Missing verification.php bundle for supported locale [{$locale}].");

            $translations = require $path;

            foreach ($required as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $translations,
                    "Missing verification translation key [{$key}] for locale [{$locale}]."
                );
                $this->assertIsString(
                    $translations[$key],
                    "Verification translation key [{$key}] must resolve to a string for locale [{$locale}]."
                );
                $this->assertNotSame('', trim($translations[$key]), "Empty verification translation [{$key}] for locale [{$locale}].");
            }
        }
    }
}
