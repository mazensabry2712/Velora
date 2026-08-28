<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class TenantPasswordResetContractTest extends TestCase
{
    public function test_password_recovery_routes_are_registered_with_expected_methods(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('password.request'));
        $this->assertNotNull(Route::getRoutes()->getByName('password.email'));
        $this->assertNotNull(Route::getRoutes()->getByName('password.reset'));
        $this->assertNotNull(Route::getRoutes()->getByName('password.update'));

        $this->assertSame(['GET', 'HEAD'], Route::getRoutes()->getByName('password.request')->methods());
        $this->assertSame(['POST'], Route::getRoutes()->getByName('password.email')->methods());
        $this->assertSame(['GET', 'HEAD'], Route::getRoutes()->getByName('password.reset')->methods());
        $this->assertSame(['POST'], Route::getRoutes()->getByName('password.update')->methods());
    }

    public function test_password_recovery_translation_bundle_is_complete_for_every_supported_locale(): void
    {
        $keys = [
            'title', 'heading', 'description', 'send_link', 'back_to_login',
            'secure_recovery', 'reset_title', 'reset_heading', 'reset_description',
            'confirm_password', 'update_password', 'token_note', 'sent',
            'reset_success', 'email_subject',
        ];

        foreach (config('localizer.supported_locales', []) as $locale) {
            $translations = require base_path("lang/{$locale}/password_reset.php");
            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $translations, "Missing password reset key [{$key}] for locale [{$locale}].");
                $this->assertNotSame('', trim((string) $translations[$key]), "Empty password reset key [{$key}] for locale [{$locale}].");
            }
        }
    }

    public function test_reset_pages_use_tenant_locale_and_do_not_expose_plain_tokens_in_markup(): void
    {
        $forgot = file_get_contents(resource_path('views/auth/forgot-password.blade.php'));
        $reset = file_get_contents(resource_path('views/auth/reset-password.blade.php'));

        $this->assertStringContainsString('app()->getLocale()', $forgot);
        $this->assertStringContainsString('app()->getLocale()', $reset);
        $this->assertStringContainsString("__('password_reset.", $forgot);
        $this->assertStringContainsString("__('password_reset.", $reset);
        $this->assertStringNotContainsString("{{ \$token }}", $reset);
    }
}
