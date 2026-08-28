<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class TenantLoginLocaleUiTest extends TestCase
{
    public function test_login_page_uses_translation_catalog_for_core_labels(): void
    {
        $this->app->setLocale('fr');

        $response = $this->withServerVariables([
            'HTTP_HOST' => env('APP_DOMAIN', 'velora.test'),
            'SERVER_NAME' => env('APP_DOMAIN', 'velora.test'),
        ])->get('/login');

        $response->assertOk();
        $response->assertSee('lang="fr"', false);
        $response->assertSee('dir="ltr"', false);
        $response->assertSee(__('Login'), false);
        $response->assertSee(__('Password'), false);
        $response->assertSee(__('Remember me'), false);
        $response->assertDontSee('{{ $isArabic', false);
    }

    public function test_login_page_exposes_a_single_language_control_group(): void
    {
        $this->app->setLocale('fr');

        $response = $this->withServerVariables([
            'HTTP_HOST' => env('APP_DOMAIN', 'velora.test'),
            'SERVER_NAME' => env('APP_DOMAIN', 'velora.test'),
        ])->get('/login');

        $response->assertOk();
        $response->assertSee('aria-label', false);
        $response->assertSee('tenant.change.language', false);
    }
}
