<?php

namespace Tests\Unit\Payments;

use App\Payments\Gateways\MoyasarGateway;
use App\Services\MoyasarService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('unit')]
#[Group('payments')]
#[Group('moyasar')]
class MoyasarGatewayTest extends TestCase
{
    // ── createCheckout ─────────────────────────────────────────────────

    #[Test]
    public function create_checkout_stores_session_data_and_returns_route(): void
    {
        $moyasarSvc = $this->createMock(MoyasarService::class);
        $gateway    = new MoyasarGateway($moyasarSvc);

        $url = $gateway->createCheckout([
            'plan_id'   => 3,
            'plan_name' => 'Business',
            'amount'    => 199.00,
            'currency'  => 'SAR',
        ]);

        // Returns a local route URL
        $this->assertStringContainsString('moyasar', $url);

        // Session was populated
        $this->assertEquals(3, session('moyasar_plan_id'));
        $this->assertEquals(19900, session('moyasar_amount'));   // 199.00 × 100 halalas
        $this->assertEquals('Business', session('moyasar_plan_name'));
        $this->assertEquals('SAR', session('moyasar_currency'));
    }

    #[Test]
    public function create_checkout_converts_amount_to_halalas_correctly(): void
    {
        $moyasarSvc = $this->createMock(MoyasarService::class);
        $gateway    = new MoyasarGateway($moyasarSvc);

        $gateway->createCheckout(['plan_id' => 1, 'amount' => 99.50, 'currency' => 'SAR']);

        $this->assertEquals(9950, session('moyasar_amount'));
    }

    #[Test]
    public function create_checkout_throws_when_plan_id_missing(): void
    {
        $moyasarSvc = $this->createMock(MoyasarService::class);
        $gateway    = new MoyasarGateway($moyasarSvc);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('plan_id is required');

        $gateway->createCheckout(['amount' => 199.00]);
    }

    #[Test]
    public function create_checkout_defaults_currency_to_sar(): void
    {
        $moyasarSvc = $this->createMock(MoyasarService::class);
        $gateway    = new MoyasarGateway($moyasarSvc);

        $gateway->createCheckout(['plan_id' => 2, 'amount' => 50.00]);

        $this->assertEquals('SAR', session('moyasar_currency'));
    }

    // ── verifyPayment ──────────────────────────────────────────────────

    #[Test]
    public function verify_payment_returns_true_when_status_is_paid(): void
    {
        $moyasarSvc = $this->createMock(MoyasarService::class);
        $moyasarSvc->method('verifyPayment')
            ->willReturn(['status' => 'paid', 'amount' => 19900]);

        $gateway = new MoyasarGateway($moyasarSvc);

        $this->assertTrue($gateway->verifyPayment(['payment_id' => 'pay_abc123']));
    }

    #[Test]
    public function verify_payment_returns_false_when_status_is_not_paid(): void
    {
        $moyasarSvc = $this->createMock(MoyasarService::class);
        $moyasarSvc->method('verifyPayment')
            ->willReturn(['status' => 'failed', 'amount' => 19900]);

        $gateway = new MoyasarGateway($moyasarSvc);

        $this->assertFalse($gateway->verifyPayment(['payment_id' => 'pay_abc123']));
    }

    #[Test]
    public function verify_payment_returns_false_when_service_throws(): void
    {
        $moyasarSvc = $this->createMock(MoyasarService::class);
        $moyasarSvc->method('verifyPayment')
            ->willThrowException(new \RuntimeException('Moyasar API error'));

        $gateway = new MoyasarGateway($moyasarSvc);

        $this->assertFalse($gateway->verifyPayment(['payment_id' => 'pay_fail']));
    }

    #[Test]
    public function verify_payment_throws_when_payment_id_missing(): void
    {
        $moyasarSvc = $this->createMock(MoyasarService::class);
        $gateway    = new MoyasarGateway($moyasarSvc);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payment_id is required');

        $gateway->verifyPayment([]);
    }

    // ── refund ─────────────────────────────────────────────────────────

    #[Test]
    public function refund_returns_false_because_moyasar_refunds_are_manual(): void
    {
        $moyasarSvc = $this->createMock(MoyasarService::class);
        $gateway    = new MoyasarGateway($moyasarSvc);

        $this->assertFalse($gateway->refund('txn_moyasar_001'));
    }

    // ── getPublishableKey ──────────────────────────────────────────────

    #[Test]
    public function get_publishable_key_delegates_to_moyasar_service(): void
    {
        $moyasarSvc = $this->createMock(MoyasarService::class);
        $moyasarSvc->expects($this->once())
            ->method('getPublishableKey')
            ->willReturn('pk_test_moyasar_key');

        $gateway = new MoyasarGateway($moyasarSvc);

        $this->assertEquals('pk_test_moyasar_key', $gateway->getPublishableKey());
    }
}
