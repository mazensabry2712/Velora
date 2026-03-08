<?php

namespace Tests\Unit\Payments;

use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\Gateways\MoyasarGateway;
use App\Payments\Gateways\PaymobGateway;
use App\Payments\Gateways\PayPalGateway;
use App\Payments\Gateways\StripeGateway;
use App\Payments\PaymentGatewayManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

#[Group('unit')]
#[Group('payments')]
class PaymentGatewayManagerTest extends TestCase
{
    private PaymentGatewayManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        // Provide a dummy key so StripeClient doesn't throw during container resolution
        config(['services.stripe.secret' => 'sk_test_unit_test_fake_key']);
        $this->manager = app(PaymentGatewayManager::class);
    }

    // ── driver() resolution ────────────────────────────────────────────

    #[Test]
    public function driver_stripe_returns_stripe_gateway(): void
    {
        $gateway = $this->manager->driver('stripe');

        $this->assertInstanceOf(StripeGateway::class, $gateway);
        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
    }

    #[Test]
    public function driver_moyasar_returns_moyasar_gateway(): void
    {
        $gateway = $this->manager->driver('moyasar');

        $this->assertInstanceOf(MoyasarGateway::class, $gateway);
        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
    }

    #[Test]
    public function driver_paymob_returns_paymob_gateway(): void
    {
        $gateway = $this->manager->driver('paymob');

        $this->assertInstanceOf(PaymobGateway::class, $gateway);
        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
    }

    #[Test]
    public function driver_paypal_returns_paypal_gateway(): void
    {
        $gateway = $this->manager->driver('paypal');

        $this->assertInstanceOf(PayPalGateway::class, $gateway);
        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
    }

    #[Test]
    public function driver_is_case_insensitive(): void
    {
        $this->assertInstanceOf(StripeGateway::class, $this->manager->driver('STRIPE'));
        $this->assertInstanceOf(StripeGateway::class, $this->manager->driver('Stripe'));
        $this->assertInstanceOf(MoyasarGateway::class, $this->manager->driver('MOYASAR'));
    }

    #[Test]
    public function driver_throws_for_unknown_gateway(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment gateway [hyperpay] is not supported.');

        $this->manager->driver('hyperpay');
    }

    #[Test]
    public function driver_throws_for_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->manager->driver('');
    }

    // ── supported() & has() ────────────────────────────────────────────

    #[Test]
    public function supported_returns_all_registered_gateway_keys(): void
    {
        $supported = $this->manager->supported();

        $this->assertContains('stripe', $supported);
        $this->assertContains('moyasar', $supported);
        $this->assertContains('paymob', $supported);
        $this->assertContains('paypal', $supported);
    }

    #[Test]
    #[DataProvider('knownGatewaysProvider')]
    public function has_returns_true_for_known_gateways(string $key): void
    {
        $this->assertTrue($this->manager->has($key));
    }

    #[Test]
    public function has_returns_false_for_unknown_gateway(): void
    {
        $this->assertFalse($this->manager->has('flutterwave'));
        $this->assertFalse($this->manager->has(''));
    }

    // ── IoC singleton ──────────────────────────────────────────────────

    #[Test]
    public function manager_is_registered_as_singleton(): void
    {
        $first  = app(PaymentGatewayManager::class);
        $second = app(PaymentGatewayManager::class);

        $this->assertSame($first, $second);
    }

    // ── Data providers ─────────────────────────────────────────────────

    public static function knownGatewaysProvider(): array
    {
        return [
            ['stripe'],
            ['moyasar'],
            ['paymob'],
            ['paypal'],
        ];
    }
}
