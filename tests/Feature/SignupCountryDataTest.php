<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class SignupCountryDataTest extends TestCase
{
    public function test_signup_country_catalog_is_populated(): void
    {
        $countries = config('localizer.countries', []);

        $this->assertCount(249, $countries);
        $this->assertArrayHasKey('EG', $countries);
        $this->assertSame('Egypt', $countries['EG']['name']);
        $this->assertSame('🇪🇬', $countries['EG']['flag']);
        $this->assertArrayHasKey('US', $countries);
        $this->assertArrayHasKey('SA', $countries);
        $this->assertArrayHasKey('AE', $countries);
    }

    public function test_signup_page_renders_country_options(): void
    {
        $response = $this->get('/signup');

        $response->assertSuccessful();
        $response->assertSee('name="country"', false);
        $response->assertSee('value="EG"', false);
        $response->assertSee('Egypt', false);
        $response->assertSee('value="US"', false);
        $response->assertSee('United States', false);
        $response->assertSee('value="SA"', false);
        $response->assertSee('Saudi Arabia', false);
    }
}
