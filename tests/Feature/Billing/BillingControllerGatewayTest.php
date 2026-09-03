<?php

namespace Tests\Feature\Billing;

use App\Domain\Shared\Contracts\PaymentGatewayResolver;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\PaymentGatewayManager;
use App\Services\GeoService;
use App\Services\MoyasarService;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('feature')]
#[Group('billing')]
class BillingControllerGatewayTest extends TenantTestCase
{
    private int $planId;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.secret' => 'sk_test_feature_test_fake_key']);

        $centralConn = config('tenancy.database.central_connection', config('database.default', 'mysql'));
        $this->planId = DB::connection($centralConn)->table('subscription_plans')->insertGetId([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 49.00,
            'billing_cycle' => 'monthly',
            'is_active' => 1,
            'is_popular' => 0,
            'trial_days' => 14,
            'stripe_price_id' => 'price_pro_usd',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mock(GeoService::class, function ($mock) {
            $mock->shouldReceive('getPlanPrice')->andReturn(null);
            $mock->shouldReceive('calculateTax')->andReturn(0.0);
        });
    }

    #[Test]
    public function checkout_routes_us_country_to_stripe_gateway(): void
    {
        $this->mockGatewayForCountry('US', 'stripe', 'https://checkout.stripe.com/pay/cs_test_abc');

        $this->actingAs($this->admin)
            ->withSession(['detected_country' => 'US'])
            ->post('/billing/checkout', ['plan_id' => $this->planId])
            ->assertRedirect('https://checkout.stripe.com/pay/cs_test_abc');
    }

    #[Test]
    public function checkout_routes_sa_country_to_moyasar_gateway(): void
    {
        $this->mockGatewayForCountry('SA', 'moyasar', route('billing.moyasar.pay'));

        $this->actingAs($this->admin)
            ->withSession(['detected_country' => 'SA'])
            ->post('/billing/checkout', ['plan_id' => $this->planId])
            ->assertRedirect();
    }

    #[Test]
    public function checkout_routes_eg_country_to_paymob_gateway(): void
    {
        $this->mockGatewayForCountry(
            'EG',
            'paymob',
            'https://accept.paymob.com/api/acceptance/iframes/67890?payment_token=pmk_token'
        );

        $this->actingAs($this->admin)
            ->withSession(['detected_country' => 'EG'])
            ->post('/billing/checkout', ['plan_id' => $this->planId])
            ->assertRedirect('https://accept.paymob.com/api/acceptance/iframes/67890?payment_token=pmk_token');
    }

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

        $mockResolver = $this->createMock(PaymentGatewayResolver::class);
        $mockResolver->method('forCountry')->willReturn(['stripe']);

        $this->app->instance(PaymentGatewayManager::class, $mockManager);
        $this->app->instance(PaymentGatewayResolver::class, $mockResolver);

        $this->actingAs($this->admin)
            ->withSession(['detected_country' => 'US'])
            ->post('/billing/checkout', ['plan_id' => $this->planId]);

        $this->assertEquals($this->planId, $capturedData['plan_id']);
        $this->assertEquals('Pro', $capturedData['plan_name']);
        $this->assertEquals($this->tenant->id, $capturedData['tenant_id']);
        $this->assertArrayHasKey('success_url', $capturedData);
        $this->assertArrayHasKey('cancel_url', $capturedData);
        $this->assertArrayHasKey('amount', $capturedData);
        $this->assertArrayHasKey('currency', $capturedData);
    }

    #[Test]
    public function checkout_redirects_back_with_error_when_gateway_throws(): void
    {
        $mockGateway = $this->createMock(PaymentGatewayInterface::class);
        $mockGateway->method('createCheckout')->willThrowException(new \Exception('Gateway connection failed'));

        $mockManager = $this->createMock(PaymentGatewayManager::class);
        $mockManager->method('driver')->willReturn($mockGateway);

        $mockResolver = $this->createMock(PaymentGatewayResolver::class);
        $mockResolver->method('forCountry')->willReturn(['stripe']);

        $this->app->instance(PaymentGatewayManager::class, $mockManager);
        $this->app->instance(PaymentGatewayResolver::class, $mockResolver);

        $this->from('/billing/expired')
            ->actingAs($this->admin)
            ->withSession(['detected_country' => 'US'])
            ->post('/billing/checkout', ['plan_id' => $this->planId])
            ->assertSessionHasErrors('checkout');
    }

    #[Test]
    public function checkout_rejects_invalid_plan_id(): void
    {
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

    #[Test]
    public function moyasar_browser_callback_only_verifies_payment_and_never_activates_subscription(): void
    {
        $paymentId = 'pay_callback_001';

        $moyasar = $this->createMock(MoyasarService::class);
        $moyasar->expects($this->once())
            ->method('verifyPayment')
            ->with($paymentId)
            ->willReturn([
                'id' => $paymentId,
                'status' => 'paid',
                'amount' => 4900,
                'currency' => 'SAR',
                'metadata' => [
                    'tenant_id' => $this->tenant->id,
                    'plan_id' => $this->planId,
                ],
            ]);
        $moyasar->expects($this->never())->method('activateSubscription');

        $this->app->instance(MoyasarService::class, $moyasar);

        $this->actingAs($this->admin)
            ->withSession([
                'moyasar_plan_id' => $this->planId,
                'moyasar_amount' => 4900,
                'moyasar_currency' => 'SAR',
            ])
            ->get(route('billing.moyasar.callback', ['id' => $paymentId]))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success', 'تم استلام الدفع بنجاح، وسيتم تفعيل الاشتراك تلقائيًا بعد تأكيد مزود الدفع.')
            ->assertSessionMissing('moyasar_plan_id');
    }

    #[Test]
    public function moyasar_browser_callback_rejects_amount_or_currency_mismatch(): void
    {
        $paymentId = 'pay_callback_mismatch_001';

        $moyasar = $this->createMock(MoyasarService::class);
        $moyasar->expects($this->once())
            ->method('verifyPayment')
            ->with($paymentId)
            ->willReturn([
                'id' => $paymentId,
                'status' => 'paid',
                'amount' => 5000,
                'currency' => 'SAR',
            ]);
        $moyasar->expects($this->never())->method('activateSubscription');

        $this->app->instance(MoyasarService::class, $moyasar);

        $this->from('/billing/expired')
            ->actingAs($this->admin)
            ->withSession([
                'moyasar_plan_id' => $this->planId,
                'moyasar_amount' => 4900,
                'moyasar_currency' => 'SAR',
            ])
            ->get(route('billing.moyasar.callback', ['id' => $paymentId]))
            ->assertRedirect(route('billing.expired'))
            ->assertSessionHasErrors('payment');
    }

    private function mockGatewayForCountry(string $country, string $expectedGateway, string $redirectsTo): void
    {
        $mockGateway = $this->createMock(PaymentGatewayInterface::class);
        $mockGateway->method('createCheckout')->willReturn($redirectsTo);

        $mockManager = $this->createMock(PaymentGatewayManager::class);
        $mockManager->expects($this->once())
            ->method('driver')
            ->with($expectedGateway)
            ->willReturn($mockGateway);

        $mockResolver = $this->createMock(PaymentGatewayResolver::class);
        $mockResolver->method('forCountry')
            ->with(strtoupper($country))
            ->willReturn([$expectedGateway]);

        $this->app->instance(PaymentGatewayManager::class, $mockManager);
        $this->app->instance(PaymentGatewayResolver::class, $mockResolver);
    }
}
