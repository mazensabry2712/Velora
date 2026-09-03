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
