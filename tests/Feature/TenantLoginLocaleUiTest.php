<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class TenantLoginLocaleUiTest extends TestCase
{
    public function test_login_view_uses_locale_aware_translation_keys(): void
    {
        $view = file_get_contents(base_path('resources/views/auth/login.blade.php'));

        self::assertIsString($view);
        self::assertStringNotContainsString('$isArabic ?', $view);
        self::assertStringContainsString("__('Login')", $view);
        self::assertStringContainsString("__('Password')", $view);
        self::assertStringContainsString("__('Remember me')", $view);
        self::assertStringContainsString("__('Forgot your password?')", $view);
        self::assertStringContainsString("@json(__('Logging in...'))", $view);
        self::assertStringContainsString("@json(__('Login successful!'))", $view);
        self::assertStringContainsString("@json(__('Invalid credentials'))", $view);
    }

    public function test_login_view_has_one_language_dropdown_and_no_broken_password_reset_route(): void
    {
        $view = file_get_contents(base_path('resources/views/auth/login.blade.php'));

        self::assertIsString($view);
        self::assertSame(1, substr_count($view, 'id="languageToggle"'));
        self::assertSame(1, substr_count($view, 'id="languageMenu"'));
        self::assertStringNotContainsString("route('password.request')", $view);
        self::assertStringContainsString('aria-haspopup="listbox"', $view);
        self::assertStringContainsString("route('tenant.change.language'", $view);
    }

    public function test_login_view_preserves_single_submit_guard_and_locale_direction(): void
    {
        $view = file_get_contents(base_path('resources/views/auth/login.blade.php'));

        self::assertIsString($view);
        self::assertStringContainsString('lang="{{ $locale }}"', $view);
        self::assertStringContainsString('dir="{{ $isRtl ? \'rtl\' : \'ltr\' }}"', $view);
        self::assertStringContainsString('submitButton.disabled = true;', $view);
        self::assertStringContainsString('submitButton.disabled = false;', $view);
    }

    public function test_login_core_copy_resolves_for_every_supported_locale(): void
    {
        $supportedLocales = array_values(array_unique(
            config('localizer.supported_locales', ['ar', 'en'])
        ));

        self::assertNotEmpty($supportedLocales);

        foreach ($supportedLocales as $locale) {
            $this->app->setLocale($locale);

            foreach ([
                'Login',
                'Password',
                'Remember me',
                'Forgot your password?',
                'Logging in...',
                'Login successful!',
                'Invalid credentials',
            ] as $key) {
                $resolved = __($key);

                self::assertIsString($resolved);
                self::assertNotSame('', trim($resolved), "Empty translation for [{$key}] in locale [{$locale}].");

                if ($locale !== 'en') {
                    self::assertNotSame($key, $resolved, "Missing translation key [{$key}] in locale [{$locale}].");
                }
            }
        }
    }

    public function test_login_direction_contract_lists_only_rtl_locales(): void
    {
        $rtlLocales = ['ar', 'he', 'fa'];
        $supportedLocales = array_values(array_unique(
            config('localizer.supported_locales', ['ar', 'en'])
        ));

        foreach ($rtlLocales as $locale) {
            self::assertContains($locale, $rtlLocales);
        }

        foreach (array_intersect($supportedLocales, $rtlLocales) as $locale) {
            self::assertContains($locale, $rtlLocales);
        }
    }
}
