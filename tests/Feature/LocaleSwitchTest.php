<?php

namespace Tests\Feature;

use App\Models\CountryPricing;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    private string $host;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = env('APP_DOMAIN', 'velora.test');

        CountryPricing::create([
            'country_code'    => 'GLOBAL',
            'country_name'    => 'Global',
            'price'           => 9.99,
            'currency'        => 'USD',
            'payment_methods' => json_encode(['stripe']),
            'is_active'       => true,
        ]);

        CountryPricing::create([
            'country_code'    => 'EG',
            'country_name'    => 'Egypt',
            'price'           => 29.00,
            'currency'        => 'EGP',
            'lang'            => 'ar',
            'payment_methods' => json_encode(['paymob', 'stripe']),
            'is_active'       => true,
        ]);
    }

    public function test_egypt_country_pricing_has_lang_column(): void
    {
        $eg = CountryPricing::where('country_code', 'EG')->first();

        $this->assertNotNull($eg);
        $columns = array_keys($eg->toArray());
        $this->assertContains('lang', $columns);
        $this->assertEquals('ar', $eg->lang);
    }

    public function test_allow_manual_language_switch_is_enabled(): void
    {
        $value = SystemSetting::get('allow_manual_language_switch', 'NOT_SET');

        $this->assertNotSame('NOT_SET', $value);
        $this->assertTrue((bool) $value);
    }

    public function test_set_country_response_includes_locale_cookie(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => $this->host, 'SERVER_NAME' => $this->host])
            ->postJson('/pricing/set-country', [
                'country_code' => 'EG',
                'lang'         => 'ar',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
        $response->assertCookie('velora_locale_override', 'ar');
    }

    public function test_landing_renders_arabic_when_locale_cookie_is_ar(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => $this->host, 'SERVER_NAME' => $this->host])
            ->withCookie('velora_locale_override', 'ar')
            ->get('/');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertDontSee('landing.nav_features', false);
        $response->assertDontSee('landing.hero_badge', false);
    }

    public function test_landing_translations_and_metadata_resolve_for_english(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => $this->host, 'SERVER_NAME' => $this->host])
            ->get('/en');

        $response->assertStatus(200);
        $response->assertSee('Features', false);
        $response->assertSee('How it works', false);
        $response->assertSee('Pricing', false);
        // The ampersand is HTML-escaped inside the <title> element.
        $response->assertSee('Velora — Smart Booking &amp; Queue Management', false);
        $response->assertSee('Smart appointment booking and queue management for small businesses', false);
        $response->assertDontSee('landing.', false);
    }

    public function test_full_roundtrip_post_set_country_then_get_landing(): void
    {
        $serverVars = ['HTTP_HOST' => $this->host, 'SERVER_NAME' => $this->host];

        $post = $this->withServerVariables($serverVars)
            ->postJson('/pricing/set-country', [
                'country_code' => 'EG',
                'lang'         => 'ar',
            ]);

        $post->assertStatus(200);

        $get = $this->withServerVariables($serverVars)
            ->withSession(['central_locale' => 'ar'])
            ->get('/');

        $get->assertStatus(200);
        $get->assertSee('dir="rtl"', false);
    }
}
