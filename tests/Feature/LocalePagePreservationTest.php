<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\CheckMaintenanceMode;
use Tests\TestCase;

class LocalePagePreservationTest extends TestCase
{
    protected function withoutMaintenanceMiddleware(): static
    {
        $this->withoutMiddleware(CheckMaintenanceMode::class);

        return $this;
    }

    public function test_language_switch_preserves_signup_page(): void
    {
        $response = $this->withoutMaintenanceMiddleware()->withServerVariables([
            'HTTP_HOST' => env('APP_DOMAIN', 'velora.test'),
            'SERVER_NAME' => env('APP_DOMAIN', 'velora.test'),
        ])->withHeaders([
            'Referer' => 'http://' . env('APP_DOMAIN', 'velora.test') . '/signup',
        ])->get('/lang/en');

        $response->assertRedirect('/en/signup');
    }

    public function test_language_switch_from_localized_signup_to_default_locale_preserves_page(): void
    {
        $response = $this->withoutMaintenanceMiddleware()->withServerVariables([
            'HTTP_HOST' => env('APP_DOMAIN', 'velora.test'),
            'SERVER_NAME' => env('APP_DOMAIN', 'velora.test'),
        ])->withHeaders([
            'Referer' => 'http://' . env('APP_DOMAIN', 'velora.test') . '/en/signup',
        ])->get('/lang/ar');

        $response->assertRedirect('/signup');
    }
}
