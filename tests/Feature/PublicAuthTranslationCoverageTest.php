<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class PublicAuthTranslationCoverageTest extends TestCase
{
    public function test_signup_and_login_copy_has_non_english_translation_for_every_supported_locale(): void
    {
        $locales = array_values(array_unique(
            config('localizer.supported_locales', [
                'ar', 'en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'tr', 'hi', 'ko', 'nl', 'id',
            ])
        ));

        $signupKeys = [
            'landing.signup_hero_line1', 'landing.signup_hero_line2', 'landing.signup_hero_sub',
            'landing.signup_benefit_1', 'landing.signup_benefit_2', 'landing.signup_benefit_3', 'landing.signup_benefit_4',
            'landing.signup_what_happens_title', 'landing.signup_step1_title', 'landing.signup_step1_desc',
            'landing.signup_step2_title', 'landing.signup_step2_desc', 'landing.signup_step3_title', 'landing.signup_step3_desc',
            'landing.signup_social_proof', 'landing.signup_back', 'landing.signup_form_title', 'landing.signup_form_sub',
            'landing.signup_business_name', 'landing.signup_business_type', 'landing.signup_booking_slug',
            'landing.signup_booking_slug_hint', 'landing.signup_email', 'landing.signup_password', 'landing.signup_password_hint',
            'landing.signup_password_confirmation', 'landing.signup_country', 'landing.signup_admin_locale',
            'landing.signup_terms_prefix', 'landing.signup_terms', 'landing.signup_and', 'landing.signup_privacy',
            'landing.signup_coupon_question', 'landing.signup_coupon_placeholder', 'landing.signup_submit',
            'landing.signup_existing', 'landing.signup_login', 'landing.signup_isolated_data',
            'landing.signup_type_salon', 'landing.signup_type_barber', 'landing.signup_type_clinic',
            'landing.signup_type_spa', 'landing.signup_type_gym', 'landing.signup_type_restaurant',
            'landing.signup_type_studio', 'landing.signup_type_school', 'landing.signup_type_other',
        ];

        $loginKeys = [
            'messages.login', 'messages.login_to_account', 'messages.password', 'messages.remember_me',
            'messages.loading', 'messages.login_success', 'messages.login_failed',
        ];

        foreach ($locales as $locale) {
            $this->app->setLocale($locale);

            foreach ($signupKeys as $key) {
                $resolved = __($key);
                $this->assertIsString($resolved, "Translation [{$key}] in [{$locale}] must be a string.");
                $this->assertNotSame('', trim($resolved), "Translation [{$key}] in [{$locale}] is empty.");
                $this->assertNotSame($key, $resolved, "Missing translation [{$key}] in [{$locale}].");
            }

            foreach ($loginKeys as $key) {
                $resolved = __($key);
                $this->assertIsString($resolved, "Translation [{$key}] in [{$locale}] must be a string.");
                $this->assertNotSame('', trim($resolved), "Translation [{$key}] in [{$locale}] is empty.");
                $this->assertNotSame($key, $resolved, "Missing translation [{$key}] in [{$locale}].");
            }

            $directJsonKeys = [
                'Toggle theme',
                'Back to workspace',
                'Forgot your password?',
            ];

            foreach ($directJsonKeys as $key) {
                $resolved = __($key);
                $this->assertIsString($resolved, "Translation [{$key}] in [{$locale}] must be a string.");
                $this->assertNotSame('', trim($resolved), "Translation [{$key}] in [{$locale}] is empty.");
                if ($locale !== 'en') {
                    $this->assertNotSame($key, $resolved, "Missing translation [{$key}] in [{$locale}].");
                }
            }
        }
    }

    public function test_signup_blade_routes_visible_copy_through_translation_keys(): void
    {
        $view = file_get_contents(base_path('resources/views/landing/signup.blade.php'));
        $this->assertIsString($view);

        foreach ([
            'landing.signup_hero_line1', 'landing.signup_hero_line2', 'landing.signup_hero_sub',
            'landing.signup_benefit_1', 'landing.signup_benefit_2', 'landing.signup_benefit_3', 'landing.signup_benefit_4',
            'landing.signup_form_title', 'landing.signup_form_sub', 'landing.signup_business_name', 'landing.signup_business_type',
            'landing.signup_booking_slug', 'landing.signup_booking_slug_hint', 'landing.signup_email', 'landing.signup_password',
            'landing.signup_password_hint', 'landing.signup_password_confirmation', 'landing.signup_country',
            'landing.signup_admin_locale', 'landing.signup_terms_prefix', 'landing.signup_terms', 'landing.signup_and',
            'landing.signup_privacy', 'landing.signup_submit', 'landing.signup_existing', 'landing.signup_login',
            'landing.signup_isolated_data',
        ] as $key) {
            $this->assertStringContainsString("__('{$key}')", $view, "Signup Blade is missing translation key [{$key}].");
        }
    }

    public function test_login_blade_uses_translation_keys_for_core_copy(): void
    {
        $view = file_get_contents(base_path('resources/views/auth/login.blade.php'));
        $this->assertIsString($view);

        foreach ([
            "__('messages.login')", "__('messages.password')", "__('messages.remember_me')",
            "__('messages.login_to_account')", "@json(__('messages.loading'))",
            "@json(__('messages.login_success'))", "@json(__('messages.login_failed'))",
            "__('Toggle theme')", "__('Forgot your password?')", "__('Back to workspace')",
        ] as $needle) {
            $this->assertStringContainsString($needle, $view);
        }
    }
}
