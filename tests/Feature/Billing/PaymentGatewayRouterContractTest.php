<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Infrastructure\Payments\PaymentGatewayRouter;
use App\Models\CountryPricing;
use App\Models\SystemSetting;
use App\Payments\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('feature')]
#[Group('billing')]
final class PaymentGatewayRouterContractTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function router_never_returns_a_gateway_that_the_manager_cannot_resolve(): void
    {
        CountryPricing::query()->create([
            'country_code' => 'EG',
            'country_name' => 'Egypt',
            'price' => 100,
            'currency' => 'EGP',
            'lang' => 'ar',
            'payment_methods' => ['fawry', 'paymob', 'stripe'],
            'is_active' => true,
        ]);

        SystemSetting::set('fawry_enabled', true, 'boolean', 'payment_methods');
        SystemSetting::set('paymob_enabled', true, 'boolean', 'payment_methods');
        SystemSetting::set('stripe_enabled', true, 'boolean', 'payment_methods');

        $manager = app(PaymentGatewayManager::class);
        $router = new PaymentGatewayRouter($manager);
        $router->flushCache('EG');

        $resolved = $router->forCountry('EG');

        $this->assertNotContains('fawry', $resolved);
        $this->assertContains('paymob', $resolved);
        $this->assertContains('stripe', $resolved);
        $this->assertSame([], array_values(array_diff($resolved, $manager->supported())));
    }
}
