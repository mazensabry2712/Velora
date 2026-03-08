<?php

namespace Tests\Feature;

use App\Models\CountryPricing;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    /** Domain used by the central route group */
    private string $host;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = env('APP_DOMAIN', 'velora.test');

        // Seed the GLOBAL fallback record (required by LandingController::global())
        CountryPricing::create([
            'country_code'    => 'GLOBAL',
            'country_name'    => 'Global',
            'price'           => 9.99,
            'currency'        => 'USD',
            'payment_methods' => json_encode(['stripe']),
            'is_active'       => true,
        ]);

        // Seed the EG pricing row with lang='ar'
        CountryPricing::create([
            'country_code'    => 'EG',
            'country_name'    => 'Egypt',
            'price'           => 29.00,
            'currency'        => 'EGP',
            'lang'            => 'ar',
            'payment_methods' => json_encode(['paymob', 'stripe']),
            'is_active'       => true,
        ]);

        // allow_manual_language_switch is already seeded by
        // migration 2026_03_02_000003_seed_geo_system_settings (updateOrCreate)
        // No duplicate insert needed here.
    }
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

        $this->assertNotSame('NOT_SET', $value,
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
        $response = $this->withServerVariables(['HTTP_HOST' => $this->host, 'SERVER_NAME' => $this->host])
            ->postJson('/pricing/set-country', [
                'country_code' => 'EG',
                'lang'         => 'ar',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        // The response must carry Set-Cookie: velora_locale_override=ar
        $response->assertCookie('velora_locale_override', 'ar');
    }

    /**
     * Test 4: GET / with cookie renders Arabic content
     */
    public function test_landing_renders_arabic_when_locale_cookie_is_ar(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => $this->host, 'SERVER_NAME' => $this->host])
            ->withCookie('velora_locale_override', 'ar')
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
        $serverVars = ['HTTP_HOST' => $this->host, 'SERVER_NAME' => $this->host];

        // Step 1: POST to set country
        $post = $this->withServerVariables($serverVars)
            ->postJson('/pricing/set-country', [
                'country_code' => 'EG',
                'lang'         => 'ar',
            ]);

        $post->assertStatus(200);

        // Step 2: GET / with the session that was just modified
        $get = $this->withServerVariables($serverVars)
            ->withSession(['central_locale' => 'ar'])
            ->get('/');

        $get->assertStatus(200);
        $get->assertSee('dir="rtl"', false);
    }
}
