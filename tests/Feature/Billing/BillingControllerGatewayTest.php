<?php

namespace Tests\Feature\Billing;

use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\PaymentGatewayManager;
use App\Services\GeoService;
use App\Services\PaymentGatewayRouter;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

/**
 * Feature tests for BillingController's checkout flow after the
 * PaymentGatewayManager refactor.
 *
 * Extends TenantTestCase so tenancy is initialized and tenant-domain routes
 * are accessible (with tenancy middleware bypassed).
 *
 * Strategy:
 *   - PaymentGatewayManager and PaymentGatewayRouter are swapped out via app()->instance().
 *   - GeoService is mocked to avoid hitting plan_prices / country_taxes tables.
 *   - No real HTTP calls go to Stripe / Moyasar / etc.
 */
#[Group('feature')]
#[Group('billing')]
class BillingControllerGatewayTest extends TenantTestCase
{
    private int $planId;

    protected function setUp(): void
    {
        parent::setUp(); // initializes tenancy + switches default connection to tenant DB

        // Provide a dummy Stripe secret so StripeService constructor doesn't throw
        config(['services.stripe.secret' => 'sk_test_feature_test_fake_key']);

        // SubscriptionPlan now uses the central connection (see getConnectionName()).
        // Seed a plan directly into the central SQLite DB.
        $centralConn = config('tenancy.database.central_connection', 'sqlite');
        $this->planId = DB::connection($centralConn)->table('subscription_plans')->insertGetId([
            'name'            => 'Pro',
            'slug'            => 'pro',
            'price'           => 49.00,
            'billing_cycle'   => 'monthly',
            'is_active'       => 1,
            'is_popular'      => 0,
            'trial_days'      => 14,
            'stripe_price_id' => 'price_pro_usd',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Mock GeoService: return null geo price (controller falls back to plan->price)
        // and zero tax so we don't need plan_prices / country_taxes tables.
        $this->mock(GeoService::class, function ($mock) {
            $mock->shouldReceive('getPlanPrice')->andReturn(null);
            $mock->shouldReceive('calculateTax')->andReturn(0.0);
        });
    }

    // ── Gateway routing ────────────────────────────────────────────────

    #[Test]
    public function checkout_routes_us_country_to_stripe_gateway(): void
    {
        $this->mockGatewayForCountry(
            country: 'US',
            expectedGateway: 'stripe',
            redirectsTo: 'https://checkout.stripe.com/pay/cs_test_abc'
        );

        $this->actingAs($this->admin)
             ->withSession(['detected_country' => 'US'])
             ->post('/billing/checkout', ['plan_id' => $this->planId])
             ->assertRedirect('https://checkout.stripe.com/pay/cs_test_abc');
    }

    #[Test]
    public function checkout_routes_sa_country_to_moyasar_gateway(): void
    {
        $this->mockGatewayForCountry(
            country: 'SA',
            expectedGateway: 'moyasar',
            redirectsTo: route('billing.moyasar.pay')
        );

        $this->actingAs($this->admin)
             ->withSession(['detected_country' => 'SA'])
             ->post('/billing/checkout', ['plan_id' => $this->planId])
             ->assertRedirect();
    }

    #[Test]
    public function checkout_routes_eg_country_to_paymob_gateway(): void
    {
        $this->mockGatewayForCountry(
            country: 'EG',
            expectedGateway: 'paymob',
            redirectsTo: 'https://accept.paymob.com/api/acceptance/iframes/67890?payment_token=pmk_token'
        );

        $this->actingAs($this->admin)
             ->withSession(['detected_country' => 'EG'])
             ->post('/billing/checkout', ['plan_id' => $this->planId])
             ->assertRedirect('https://accept.paymob.com/api/acceptance/iframes/67890?payment_token=pmk_token');
    }

    // ── createCheckout data correctness ────────────────────────────────

    #[Test]
    public function checkout_passes_correct_tenant_and_plan_data_to_gateway(): void
    {
        $capturedData = [];

        $mockGateway = $this->createMock(PaymentGatewayInterface::class);
        $mockGateway->expects($this->once())
            ->method('createCheckout')
            ->with($this->callback(function (array $data) use (&$capturedData) {
                $capturedData = $data;
                return true;
            }))
            ->willReturn('https://checkout.stripe.com/pay/cs_test_XYZ');

        $mockManager = $this->createMock(PaymentGatewayManager::class);
        $mockManager->method('driver')->willReturn($mockGateway);

        $mockRouter = $this->createMock(PaymentGatewayRouter::class);
        $mockRouter->method('forCountry')->willReturn(['stripe']);

        $this->app->instance(PaymentGatewayManager::class, $mockManager);
        $this->app->instance(PaymentGatewayRouter::class, $mockRouter);

        $this->actingAs($this->admin)
             ->withSession(['detected_country' => 'US'])
             ->post('/billing/checkout', ['plan_id' => $this->planId]);

        // Verify correct fields passed to gateway
        $this->assertEquals($this->planId, $capturedData['plan_id']);
        $this->assertEquals('Pro', $capturedData['plan_name']);
        $this->assertEquals($this->tenant->id, $capturedData['tenant_id']);
        $this->assertArrayHasKey('success_url', $capturedData);
        $this->assertArrayHasKey('cancel_url', $capturedData);
        $this->assertArrayHasKey('amount', $capturedData);
        $this->assertArrayHasKey('currency', $capturedData);
    }

    // ── Error handling ─────────────────────────────────────────────────

    #[Test]
    public function checkout_redirects_back_with_error_when_gateway_throws(): void
    {
        $mockGateway = $this->createMock(PaymentGatewayInterface::class);
        $mockGateway->method('createCheckout')
            ->willThrowException(new \Exception('Gateway connection failed'));

        $mockManager = $this->createMock(PaymentGatewayManager::class);
        $mockManager->method('driver')->willReturn($mockGateway);

        $mockRouter = $this->createMock(PaymentGatewayRouter::class);
        $mockRouter->method('forCountry')->willReturn(['stripe']);

        $this->app->instance(PaymentGatewayManager::class, $mockManager);
        $this->app->instance(PaymentGatewayRouter::class, $mockRouter);

        $this->from('/billing/expired')
             ->actingAs($this->admin)
             ->withSession(['detected_country' => 'US'])
             ->post('/billing/checkout', ['plan_id' => $this->planId])
             ->assertSessionHasErrors('checkout');
    }

    #[Test]
    public function checkout_rejects_invalid_plan_id(): void
    {
        // plan_id 9999999 does not exist — validation rule rejects it
        $this->from('/billing/expired')
             ->actingAs($this->admin)
             ->withSession(['detected_country' => 'US'])
             ->post('/billing/checkout', ['plan_id' => 9999999])
             ->assertSessionHasErrors('plan_id');
    }

    #[Test]
    public function checkout_requires_plan_id(): void
    {
        $this->from('/billing/expired')
             ->actingAs($this->admin)
             ->withSession(['detected_country' => 'US'])
             ->post('/billing/checkout', [])
             ->assertSessionHasErrors('plan_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────

    /**
     * Bind mock PaymentGatewayManager and PaymentGatewayRouter for a given country.
     */
    private function mockGatewayForCountry(
        string $country,
        string $expectedGateway,
        string $redirectsTo
    ): void {
        $mockGateway = $this->createMock(PaymentGatewayInterface::class);
        $mockGateway->method('createCheckout')->willReturn($redirectsTo);

        $mockManager = $this->createMock(PaymentGatewayManager::class);
        $mockManager->expects($this->once())
            ->method('driver')
            ->with($expectedGateway)
            ->willReturn($mockGateway);

        $mockRouter = $this->createMock(PaymentGatewayRouter::class);
        $mockRouter->method('forCountry')
            ->with(strtoupper($country))
            ->willReturn([$expectedGateway]);

        $this->app->instance(PaymentGatewayManager::class, $mockManager);
        $this->app->instance(PaymentGatewayRouter::class, $mockRouter);
    }
}
