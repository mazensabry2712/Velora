<?php

namespace Tests\Feature\MultiRegion;

use App\Models\CountryPricing;
use App\Models\CountryTax;
use App\Models\SystemSetting;
use App\Services\GeoService;
use App\Services\PaymentGatewayRouter;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GeoLocalizationTest extends TestCase
{
    use RefreshDatabase;

    // ─── Fixtures ────────────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // GLOBAL fallback
        CountryPricing::create([
            'country_code'    => 'GLOBAL',
            'country_name'    => 'Global (Default)',
            'price'           => 39.00,
            'currency'        => 'USD',
            'payment_methods' => ['stripe', 'paypal'],
            'is_active'       => true,
        ]);

        // Egypt
        CountryPricing::create([
            'country_code'    => 'EG',
            'country_name'    => 'Egypt',
            'price'           => 199.00,
            'currency'        => 'EGP',
            'payment_methods' => ['stripe', 'fawry', 'paymob'],
            'is_active'       => true,
        ]);

        // Saudi Arabia
        CountryPricing::create([
            'country_code'    => 'SA',
            'country_name'    => 'Saudi Arabia',
            'price'           => 99.00,
            'currency'        => 'SAR',
            'payment_methods' => ['stripe', 'mada', 'moyasar'],
            'is_active'       => true,
        ]);

        // India
        CountryPricing::create([
            'country_code'    => 'IN',
            'country_name'    => 'India',
            'price'           => 799.00,
            'currency'        => 'INR',
            'payment_methods' => ['razorpay', 'stripe'],
            'is_active'       => true,
        ]);

        // Egypt VAT
        CountryTax::create([
            'country_code'   => 'EG',
            'tax_name'       => 'VAT',
            'tax_percentage' => 14.00,
            'is_active'      => true,
        ]);

        // System settings — use updateOrCreate so migrations-seeded keys are not duplicated
        SystemSetting::set('stripe_enabled',          '1',  'boolean', 'payment_methods');
        SystemSetting::set('paypal_enabled',          '1',  'boolean', 'payment_methods');
        SystemSetting::set('fawry_enabled',           '1',  'boolean', 'payment_methods');
        SystemSetting::set('paymob_enabled',          '1',  'boolean', 'payment_methods');
        SystemSetting::set('mada_enabled',            '1',  'boolean', 'payment_methods');
        SystemSetting::set('moyasar_enabled',         '0',  'boolean', 'payment_methods');
        SystemSetting::set('razorpay_enabled',        '1',  'boolean', 'payment_methods');
        SystemSetting::set('default_trial_days',      '14', 'integer', 'general');
        SystemSetting::set('registration_enabled',    '1',  'boolean', 'general');
        SystemSetting::set('geo_detection_enabled',   '1',  'boolean', 'general');
        SystemSetting::set('enable_vat_per_country',  '1',  'boolean', 'general');
    }

    // ─── 1. CountryPricing Model ─────────────────────────────────────────────

    #[Test]
    public function it_resolves_country_specific_pricing(): void
    {
        $pricing = CountryPricing::forCountry('EG');

        $this->assertSame('EG',  $pricing->country_code);
        $this->assertSame('EGP', $pricing->currency);
        $this->assertEquals(199.00, (float) $pricing->price);
    }

    #[Test]
    public function it_falls_back_to_global_for_unknown_country(): void
    {
        $pricing = CountryPricing::forCountry('ZZ');

        $this->assertSame('GLOBAL', $pricing->country_code);
        $this->assertSame('USD',    $pricing->currency);
        $this->assertEquals(39.00, (float) $pricing->price);
    }

    #[Test]
    public function it_returns_correct_payment_methods_per_country(): void
    {
        $eg = CountryPricing::forCountry('EG');
        $sa = CountryPricing::forCountry('SA');
        $in = CountryPricing::forCountry('IN');

        $this->assertContains('fawry',    $eg->payment_methods);
        $this->assertContains('paymob',   $eg->payment_methods);
        $this->assertContains('mada',     $sa->payment_methods);
        $this->assertContains('razorpay', $in->payment_methods);
        $this->assertNotContains('fawry', $in->payment_methods);
    }

    #[Test]
    public function it_formats_prices_with_correct_symbol(): void
    {
        $eg     = CountryPricing::forCountry('EG');
        $global = CountryPricing::forCountry('GLOBAL');

        $this->assertStringContainsString('199', $eg->formattedPrice());
        $this->assertStringContainsString('EGP', $eg->formattedPrice());
        $this->assertStringContainsString('$',   $global->formattedPrice());
    }

    // ─── 2. CountryTax Model ─────────────────────────────────────────────────

    #[Test]
    public function it_returns_tax_percentage_for_country(): void
    {
        $pct = CountryTax::percentageFor('EG');
        $this->assertEquals(14.0, $pct);
    }

    #[Test]
    public function it_returns_zero_tax_for_country_without_tax(): void
    {
        $pct = CountryTax::percentageFor('SA');
        $this->assertEquals(0.0, $pct);
    }

    // ─── 3. GeoService ───────────────────────────────────────────────────────

    #[Test]
    public function geo_service_calculates_tax_amount(): void
    {
        $geo = app(GeoService::class);
        $tax = $geo->calculateTax(199.0, 'EG');

        $this->assertEquals(27.86, $tax); // 199 * 0.14 = 27.86
    }

    #[Test]
    public function geo_service_returns_zero_tax_when_feature_disabled(): void
    {
        SystemSetting::set('enable_vat_per_country', '0', 'boolean', 'general');
        Cache::flush();

        $geo = app(GeoService::class);
        $tax = $geo->calculateTax(199.0, 'EG');

        $this->assertEquals(0.0, $tax);
    }

    #[Test]
    public function geo_service_returns_amount_with_tax(): void
    {
        $geo   = app(GeoService::class);
        $total = $geo->amountWithTax(199.0, 'EG');

        $this->assertEquals(226.86, $total); // 199 + 27.86
    }

    #[Test]
    public function geo_service_returns_full_pricing_context(): void
    {
        $geo = app(GeoService::class);
        $ctx = $geo->getPricingContext('EG');

        $this->assertSame('EG',  $ctx['country_code']);
        $this->assertSame('EGP', $ctx['currency']);
        $this->assertEquals(199.0, $ctx['base_price']);
        $this->assertEquals(14.0,  $ctx['tax_pct']);
        $this->assertSame('VAT',   $ctx['tax_name']);
        $this->assertGreaterThan(0, $ctx['tax_amount']);
        $this->assertEquals($ctx['base_price'] + $ctx['tax_amount'], $ctx['total_price']);
        $this->assertIsArray($ctx['payment_methods']);
        $this->assertFalse($ctx['is_global']);
    }

    #[Test]
    public function geo_service_pricing_context_falls_back_to_global(): void
    {
        $geo = app(GeoService::class);
        $ctx = $geo->getPricingContext('ZZ');

        $this->assertSame('GLOBAL', $ctx['country_code']);
        $this->assertTrue($ctx['is_global']);
        $this->assertEquals(0.0, $ctx['tax_pct']); // no tax for unknown country
    }

    // ─── 4. PaymentGatewayRouter ─────────────────────────────────────────────

    #[Test]
    public function gateway_router_returns_enabled_gateways_for_egypt(): void
    {
        $router   = app(PaymentGatewayRouter::class);
        $gateways = $router->forCountry('EG');

        $this->assertContains('stripe', $gateways);
        $this->assertContains('fawry',  $gateways);
        $this->assertContains('paymob', $gateways);
    }

    #[Test]
    public function gateway_router_filters_out_disabled_gateways(): void
    {
        // moyasar_enabled = 0 in setUp
        $router   = app(PaymentGatewayRouter::class);
        $gateways = $router->forCountry('SA');

        $this->assertContains('stripe',         $gateways);
        $this->assertContains('mada',           $gateways);
        $this->assertNotContains('moyasar',     $gateways); // explicitly disabled
    }

    #[Test]
    public function gateway_router_returns_labels_with_metadata(): void
    {
        $router   = app(PaymentGatewayRouter::class);
        $labelled = $router->forCountryWithLabels('EG');

        $this->assertIsArray($labelled);
        $this->assertNotEmpty($labelled);

        $keys   = array_column($labelled, 'key');
        $labels = array_column($labelled, 'label');

        $this->assertContains('stripe', $keys);
        $this->assertContains('Stripe', $labels);
    }

    #[Test]
    public function gateway_router_checks_gateway_availability(): void
    {
        $router = app(PaymentGatewayRouter::class);

        $this->assertTrue($router->isAvailable('fawry',   'EG'));
        $this->assertTrue($router->isAvailable('mada',    'SA'));
        $this->assertFalse($router->isAvailable('moyasar','SA')); // disabled in settings
        $this->assertFalse($router->isAvailable('fawry',  'IN')); // not in IN preferred list
    }

    // ─── 5. PricingService ───────────────────────────────────────────────────

    #[Test]
    public function pricing_service_sets_and_reads_country_override(): void
    {
        $service = app(PricingService::class);
        $service->setCountryOverride('EG');
        $pricing = $service->getPricingForCountry('EG');

        $this->assertSame('EG',  $pricing->country_code);
        $this->assertSame('EGP', $pricing->currency);
    }

    #[Test]
    public function pricing_service_summary_has_required_keys(): void
    {
        $service = app(PricingService::class);
        $summary = $service->getPricingSummary(request());

        $this->assertArrayHasKey('country_code',    $summary);
        $this->assertArrayHasKey('country_name',    $summary);
        $this->assertArrayHasKey('price',           $summary);
        $this->assertArrayHasKey('currency',        $summary);
        $this->assertArrayHasKey('formatted_price', $summary);
        $this->assertArrayHasKey('payment_methods', $summary);
        $this->assertArrayHasKey('is_global',       $summary);
    }

    // ─── 6. /pricing Page (HTTP) ─────────────────────────────────────────────

    #[Test]
    public function pricing_page_returns_200(): void
    {
        $this->get('/pricing')
             ->assertStatus(200)
             ->assertViewIs('landing.pricing')
             ->assertViewHas('pricing')
             ->assertViewHas('allPricing')
             ->assertViewHas('globalPricing')
             ->assertViewHas('taxPct')
             ->assertViewHas('taxName')
             ->assertViewHas('trialDays')
             ->assertViewHas('registrationEnabled');
    }

    #[Test]
    public function pricing_page_contains_expected_html_elements(): void
    {
        $this->get('/pricing')
             ->assertStatus(200)
             ->assertSee('pricingPage(')
             ->assertSee('set-country')
             ->assertSee('renderPaymentMethods')
             ->assertSee('openSwitcher');
    }

    #[Test]
    public function pricing_page_shows_geo_detected_country_by_default(): void
    {
        $this->withHeaders(['CF-IPCountry' => 'EG'])
             ->get('/pricing')
             ->assertStatus(200)
             ->assertSee('EG');
    }

    // ─── 7. POST /pricing/set-country ────────────────────────────────────────

    #[Test]
    public function set_country_endpoint_returns_pricing_json(): void
    {
        $this->postJson('/pricing/set-country', ['country_code' => 'EG'])
             ->assertStatus(200)
             ->assertJson(['ok' => true])
             ->assertJsonStructure([
                 'ok', 'country_code', 'country_name',
                 'price', 'currency', 'formatted_price', 'payment_methods',
             ])
             ->assertJsonPath('country_code', 'EG')
             ->assertJsonPath('currency', 'EGP');
    }

    #[Test]
    public function set_country_endpoint_accepts_lowercase_code(): void
    {
        $this->postJson('/pricing/set-country', ['country_code' => 'sa'])
             ->assertStatus(200)
             ->assertJsonPath('country_code', 'SA')
             ->assertJsonPath('currency', 'SAR');
    }

    #[Test]
    public function set_country_endpoint_validates_country_code_format(): void
    {
        $this->postJson('/pricing/set-country', ['country_code' => 'TOOLONGCODE123'])
             ->assertStatus(422);

        $this->postJson('/pricing/set-country', ['country_code' => '12'])
             ->assertStatus(422);

        $this->postJson('/pricing/set-country', ['country_code' => ''])
             ->assertStatus(422);
    }

    #[Test]
    public function set_country_endpoint_falls_back_to_global_for_unknown_country(): void
    {
        $this->postJson('/pricing/set-country', ['country_code' => 'ZZ'])
             ->assertStatus(200)
             ->assertJsonPath('country_code', 'GLOBAL');
    }

    #[Test]
    public function set_country_stores_override_in_session(): void
    {
        $this->postJson('/pricing/set-country', ['country_code' => 'SA']);

        $this->assertEquals('SA', session('pricing_country_override'));
    }

    // ─── 8. GeoService – Country Detection ───────────────────────────────────

    #[Test]
    public function geo_service_detects_country_from_cloudflare_header(): void
    {
        $geo     = app(GeoService::class);
        $request = request()->duplicate();
        $request->headers->set('CF-IPCountry', 'DE');

        $this->assertSame('DE', $geo->getCountryCode($request));
    }

    #[Test]
    public function geo_service_ignores_invalid_cloudflare_header_xx(): void
    {
        $geo     = app(GeoService::class);
        $request = request()->duplicate();
        $request->headers->set('CF-IPCountry', 'XX'); // Cloudflare unknown

        $this->assertSame('US', $geo->getCountryCode($request));
    }

    #[Test]
    public function geo_service_falls_back_to_us_with_no_headers(): void
    {
        $geo     = app(GeoService::class);
        $request = request()->duplicate();

        $this->assertSame('US', $geo->getCountryCode($request));
    }

    // ─── 9. Cache Invalidation ───────────────────────────────────────────────

    #[Test]
    public function country_pricing_cache_is_flushed_on_save(): void
    {
        // Prime the cache
        $before = CountryPricing::forCountry('EG');
        $this->assertEquals(199.00, (float) $before->price);

        // Save a changed price (triggers booted() observer → Cache::forget)
        $model        = CountryPricing::where('country_code', 'EG')->first();
        $model->price = 249.00;
        $model->save();

        // After save the cache key is gone; next call re-queries DB
        $after = CountryPricing::forCountry('EG');
        $this->assertEquals(249.00, (float) $after->price);
    }
}
