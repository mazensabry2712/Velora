<?php

namespace Tests\Unit\Payments;

use App\Payments\Gateways\StripeGateway;
use App\Services\GeoService;
use App\Services\StripeService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit')]
#[Group('payments')]
#[Group('stripe')]
class StripeGatewayTest extends TestCase
{
    // ── createCheckout ─────────────────────────────────────────────────

    #[Test]
    public function create_checkout_returns_stripe_url(): void
    {
        $fakeSession = \Stripe\Checkout\Session::constructFrom([
            'object'         => 'checkout.session',
            'id'             => 'cs_test_abc123',
            'url'            => 'https://checkout.stripe.com/pay/cs_test_abc123',
            'payment_status' => 'unpaid',
        ]);

        $stripeSvc = $this->createMock(StripeService::class);
        $stripeSvc->expects($this->once())
            ->method('createCheckoutSession')
            ->willReturn($fakeSession);

        $gateway = new StripeGateway($stripeSvc, app(GeoService::class));

        $url = $gateway->createCheckout($this->checkoutData());

        $this->assertEquals('https://checkout.stripe.com/pay/cs_test_abc123', $url);
    }

    #[Test]
    public function create_checkout_forwards_correct_data_to_stripe_service(): void
    {
        $fakeSession = \Stripe\Checkout\Session::constructFrom([
            'object'         => 'checkout.session',
            'id'             => 'cs_xyz',
            'url'            => 'https://checkout.stripe.com/pay/cs_xyz',
            'payment_status' => 'unpaid',
        ]);

        $stripeSvc = $this->createMock(StripeService::class);
        $stripeSvc->expects($this->once())
            ->method('createCheckoutSession')
            ->with(
                $this->equalTo('tenant-uuid-001'),      // tenantId
                $this->equalTo('price_stripe_001'),     // stripePriceId
                $this->equalTo('owner@velora.test'),    // customerEmail
                $this->equalTo('Velora Owner'),         // customerName
                $this->stringContains('/billing/success'),
                $this->stringContains('/billing/expired'),
                $this->isArray()
            )
            ->willReturn($fakeSession);

        $gateway = new StripeGateway($stripeSvc, app(GeoService::class));
        $gateway->createCheckout($this->checkoutData());
    }

    #[Test]
    public function create_checkout_throws_when_stripe_price_id_missing(): void
    {
        $stripeSvc = $this->createMock(StripeService::class);
        $stripeSvc->expects($this->never())->method('createCheckoutSession');

        $gateway = new StripeGateway($stripeSvc, app(GeoService::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('stripe_price_id is required');

        $data = $this->checkoutData();
        unset($data['stripe_price_id']);
        $gateway->createCheckout($data);
    }

    // ── verifyPayment ──────────────────────────────────────────────────

    #[Test]
    public function verify_payment_returns_true_when_stripe_session_is_paid(): void
    {
        $fakeSession = \Stripe\Checkout\Session::constructFrom([
            'object'         => 'checkout.session',
            'id'             => 'cs_test_abc',
            'payment_status' => 'paid',
        ]);

        $stripeSvc = $this->createMock(StripeService::class);
        $stripeSvc->method('retrieveCheckoutSession')->willReturn($fakeSession);

        $gateway = new StripeGateway($stripeSvc, app(GeoService::class));

        $this->assertTrue($gateway->verifyPayment(['session_id' => 'cs_test_abc']));
    }

    #[Test]
    public function verify_payment_returns_false_when_session_not_paid(): void
    {
        $fakeSession = \Stripe\Checkout\Session::constructFrom([
            'object'         => 'checkout.session',
            'id'             => 'cs_test_abc',
            'payment_status' => 'unpaid',
        ]);

        $stripeSvc = $this->createMock(StripeService::class);
        $stripeSvc->method('retrieveCheckoutSession')->willReturn($fakeSession);

        $gateway = new StripeGateway($stripeSvc, app(GeoService::class));

        $this->assertFalse($gateway->verifyPayment(['session_id' => 'cs_test_abc']));
    }

    #[Test]
    public function verify_payment_returns_false_when_stripe_service_throws(): void
    {
        $stripeSvc = $this->createMock(StripeService::class);
        $stripeSvc->method('retrieveCheckoutSession')
            ->willThrowException(new \Exception('Network error'));

        $gateway = new StripeGateway($stripeSvc, app(GeoService::class));

        $this->assertFalse($gateway->verifyPayment(['session_id' => 'cs_test_abc']));
    }

    #[Test]
    public function verify_payment_throws_when_session_id_missing(): void
    {
        $stripeSvc = $this->createMock(StripeService::class);
        $gateway   = new StripeGateway($stripeSvc, app(GeoService::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('session_id is required');

        $gateway->verifyPayment([]);
    }

    // ── refund ─────────────────────────────────────────────────────────

    #[Test]
    public function refund_returns_true_on_success(): void
    {
        $stripeSvc = $this->createMock(StripeService::class);
        $stripeSvc->expects($this->once())
            ->method('refund')
            ->with('pi_test_123')
            ->willReturn(\Stripe\Refund::constructFrom(['object' => 'refund', 'id' => 're_test_123', 'status' => 'succeeded']));

        $gateway = new StripeGateway($stripeSvc, app(GeoService::class));

        $this->assertTrue($gateway->refund('pi_test_123'));
    }

    #[Test]
    public function refund_returns_false_when_stripe_service_throws(): void
    {
        $stripeSvc = $this->createMock(StripeService::class);
        $stripeSvc->method('refund')
            ->willThrowException(new \Exception('Stripe error'));

        $gateway = new StripeGateway($stripeSvc, app(GeoService::class));

        $this->assertFalse($gateway->refund('pi_test_123'));
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function checkoutData(): array
    {
        return [
            'plan_id'         => 1,
            'plan_name'       => 'Pro',
            'tenant_id'       => 'tenant-uuid-001',
            'customer_email'  => 'owner@velora.test',
            'customer_name'   => 'Velora Owner',
            'success_url'     => 'https://demo.velora.test/billing/success',
            'cancel_url'      => 'https://demo.velora.test/billing/expired',
            'amount'          => 49.00,
            'currency'        => 'USD',
            'country_code'    => 'US',
            'stripe_price_id' => 'price_stripe_001',
            'metadata'        => [],
        ];
    }
}
