<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\CountryPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    /**
     * Test 1: CountryPricing has a 'lang' column for EG
     */
    public function test_egypt_country_pricing_has_lang_column(): void
    {
        $eg = CountryPricing::where('country_code', 'EG')->first();

        $this->assertNotNull($eg, 'EG CountryPricing record not found');

        $columns = array_keys($eg->toArray());
        $this->assertContains('lang', $columns,
            'CountryPricing table is MISSING the "lang" column. Got: ' . implode(', ', $columns)
        );
        $this->assertEquals('ar', $eg->lang, 'EG lang should be "ar", got: ' . $eg->lang);
    }

    /**
     * Test 2: allow_manual_language_switch is enabled
     */
    public function test_allow_manual_language_switch_is_enabled(): void
    {
        $value = SystemSetting::get('allow_manual_language_switch', 'NOT_SET');

        $this->assertNotEquals('NOT_SET', $value,
            'SystemSetting "allow_manual_language_switch" does not exist in DB'
        );

        $this->assertTrue((bool) $value,
            'SystemSetting "allow_manual_language_switch" is DISABLED (value: ' . var_export($value, true) . ')'
        );
    }

    /**
     * Test 3: POST /pricing/set-country response sets velora_locale_override cookie
     */
    public function test_set_country_response_includes_locale_cookie(): void
    {
        $response = $this->postJson('/pricing/set-country', [
            'country_code' => 'EG',
            'lang'         => 'ar',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        // The response must carry Set-Cookie: velora_locale_override=ar
        $cookies = $response->headers->getCookies();
        $locCookie = collect($cookies)->firstWhere('name', 'velora_locale_override');

        $this->assertNotNull($locCookie,
            'Response does NOT have velora_locale_override cookie. Cookies found: '
            . implode(', ', collect($cookies)->pluck('name')->all())
        );
    }

    /**
     * Test 4: GET / with cookie renders Arabic content
     */
    public function test_landing_renders_arabic_when_locale_cookie_is_ar(): void
    {
        // Simulate browser sending back the cookie
        $response = $this->withCookie('velora_locale_override', 'ar')
                         ->get('/');

        $response->assertStatus(200);

        // The html tag should have dir="rtl" and lang="ar"
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
    }

    /**
     * Test 5: Full round-trip — POST then GET (cookie-based)
     */
    public function test_full_roundtrip_post_set_country_then_get_landing(): void
    {
        // Step 1: POST to set country
        $post = $this->postJson('/pricing/set-country', [
            'country_code' => 'EG',
            'lang'         => 'ar',
        ]);

        $post->assertStatus(200);

        // Step 2: GET / with the session that was just modified
        $get = $this->withSession(['central_locale' => 'ar'])->get('/');

        $get->assertStatus(200);
        $get->assertSee('dir="rtl"', false);
    }
}
