<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class LocalePagePreservationTest extends TestCase
{
    public function test_language_switch_preserves_signup_page(): void
    {
        $response = $this->withServerVariables([
            'HTTP_HOST' => env('APP_DOMAIN', 'velora.test'),
            'SERVER_NAME' => env('APP_DOMAIN', 'velora.test'),
        ])->withHeaders([
            'Referer' => 'http://' . env('APP_DOMAIN', 'velora.test') . '/signup',
        ])->get('/lang/en');

        $response->assertRedirect('/en/signup');
    }

    public function test_language_switch_from_localized_signup_to_default_locale_preserves_page(): void
    {
        $response = $this->withServerVariables([
            'HTTP_HOST' => env('APP_DOMAIN', 'velora.test'),
            'SERVER_NAME' => env('APP_DOMAIN', 'velora.test'),
        ])->withHeaders([
            'Referer' => 'http://' . env('APP_DOMAIN', 'velora.test') . '/en/signup',
        ])->get('/lang/ar');

        $response->assertRedirect('/signup');
    }
}
