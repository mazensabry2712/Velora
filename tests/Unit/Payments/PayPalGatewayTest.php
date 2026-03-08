<?php

namespace Tests\Unit\Payments;

use App\Payments\Gateways\PayPalGateway;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests for PayPalGateway (Orders API v2).
 *
 * All HTTP calls are faked using Http::fake() — no live API calls.
 */
#[Group('unit')]
#[Group('payments')]
#[Group('paypal')]
class PayPalGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paypal.client_id'     => 'paypal_client_id',
            'services.paypal.client_secret' => 'paypal_client_secret',
            'services.paypal.mode'          => 'sandbox',
        ]);
    }

    // ── createCheckout ─────────────────────────────────────────────────

    #[Test]
    public function create_checkout_returns_payer_action_approval_url(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'access_token_abc',
                'token_type'   => 'Bearer',
            ], 200),
            'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id'     => 'ORDER_123',
                'status' => 'CREATED',
                'links'  => [
                    ['rel' => 'self',         'href' => 'https://api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_123'],
                    ['rel' => 'payer-action', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=ORDER_123'],
                ],
            ], 200),
        ]);

        $gateway = new PayPalGateway();

        $url = $gateway->createCheckout($this->checkoutData());

        $this->assertEquals('https://www.sandbox.paypal.com/checkoutnow?token=ORDER_123', $url);
    }

    #[Test]
    public function create_checkout_falls_back_to_approve_link_when_payer_action_missing(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'tk'], 200),
            'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id'     => 'ORDER_456',
                'status' => 'CREATED',
                'links'  => [
                    ['rel' => 'approve', 'href' => 'https://paypal.com/approve?token=ORDER_456'],
                ],
            ], 200),
        ]);

        $gateway = new PayPalGateway();
        $url     = $gateway->createCheckout($this->checkoutData());

        $this->assertStringContainsString('approve', $url);
    }

    #[Test]
    public function create_checkout_sends_correct_amount_and_currency_to_paypal(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'tk'], 200),
            'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id'     => 'ORDER_789',
                'status' => 'CREATED',
                'links'  => [['rel' => 'payer-action', 'href' => 'https://paypal.com/pay']],
            ], 200),
        ]);

        $gateway = new PayPalGateway();
        $gateway->createCheckout($this->checkoutData());

        Http::assertSent(function (HttpRequest $request) {
            if (!str_contains($request->url(), '/v2/checkout/orders')) {
                return false;
            }
            $data = $request->data();
            return $data['purchase_units'][0]['amount']['value'] === '49.00'
                && $data['purchase_units'][0]['amount']['currency_code'] === 'USD';
        });
    }

    #[Test]
    public function create_checkout_throws_when_no_approval_link_returned(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'tk'], 200),
            'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id'     => 'ORDER_999',
                'status' => 'CREATED',
                'links'  => [], // no links at all
            ], 200),
        ]);

        $gateway = new PayPalGateway();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PayPal did not return an approval URL');

        $gateway->createCheckout($this->checkoutData());
    }

    #[Test]
    public function create_checkout_throws_when_oauth_fails(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['error' => 'invalid_client'], 401),
        ]);

        $gateway = new PayPalGateway();

        $this->expectException(\Exception::class);

        $gateway->createCheckout($this->checkoutData());
    }

    // ── verifyPayment ──────────────────────────────────────────────────

    #[Test]
    public function verify_payment_returns_true_when_order_status_is_completed(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token'                           => Http::response(['access_token' => 'tk'], 200),
            'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_123/capture'       => Http::response(['status' => 'COMPLETED'], 201),
        ]);

        $gateway = new PayPalGateway();

        $this->assertTrue($gateway->verifyPayment(['order_id' => 'ORDER_123']));
    }

    #[Test]
    public function verify_payment_returns_false_when_order_not_completed(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token'                           => Http::response(['access_token' => 'tk'], 200),
            'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_PEND/capture'      => Http::response(['status' => 'PENDING'], 200),
        ]);

        $gateway = new PayPalGateway();

        $this->assertFalse($gateway->verifyPayment(['order_id' => 'ORDER_PEND']));
    }

    #[Test]
    public function verify_payment_returns_false_when_capture_api_fails(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token'                           => Http::response(['access_token' => 'tk'], 200),
            'api-m.sandbox.paypal.com/v2/checkout/orders/ORDER_ERR/capture'       => Http::response(['error' => 'ORDER_ALREADY_CAPTURED'], 422),
        ]);

        $gateway = new PayPalGateway();

        $this->assertFalse($gateway->verifyPayment(['order_id' => 'ORDER_ERR']));
    }

    #[Test]
    public function verify_payment_throws_when_order_id_missing(): void
    {
        $gateway = new PayPalGateway();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('order_id is required');

        $gateway->verifyPayment([]);
    }

    // ── refund ─────────────────────────────────────────────────────────

    #[Test]
    public function refund_returns_true_on_successful_api_call(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token'                                   => Http::response(['access_token' => 'tk'], 200),
            'api-m.sandbox.paypal.com/v2/payments/captures/CAPTURE_001/refund'            => Http::response(['id' => 'REF_001', 'status' => 'COMPLETED'], 201),
        ]);

        $gateway = new PayPalGateway();

        $this->assertTrue($gateway->refund('CAPTURE_001'));
    }

    #[Test]
    public function refund_returns_false_when_api_fails(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token'                                   => Http::response(['access_token' => 'tk'], 200),
            'api-m.sandbox.paypal.com/v2/payments/captures/CAPTURE_ERR/refund'            => Http::response(['error' => 'CAPTURE_FULLY_REFUNDED'], 422),
        ]);

        $gateway = new PayPalGateway();

        $this->assertFalse($gateway->refund('CAPTURE_ERR'));
    }

    // ── Sandbox vs Live URL ─────────────────────────────────────────────

    #[Test]
    public function uses_sandbox_url_when_mode_is_sandbox(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/*' => Http::response(['access_token' => 'tk'], 200),
        ]);

        // Should NOT hit live URL
        Http::fake([
            'api-m.paypal.com/*' => fn () => $this->fail('Live URL should not be called in sandbox mode.'),
        ]);

        config(['services.paypal.mode' => 'sandbox']);
        $gateway = new PayPalGateway();

        // Will throw on Orders because we don't fake that, but that's fine —
        // we just need to ensure the oauth call went to sandbox URL.
        try {
            $gateway->createCheckout($this->checkoutData());
        } catch (\Exception) {
        }

        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'api-m.sandbox.paypal.com'));
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function checkoutData(): array
    {
        return [
            'plan_id'        => 1,
            'tenant_id'      => 'tenant-us-001',
            'customer_email' => 'user@velora.test',
            'customer_name'  => 'John Doe',
            'success_url'    => 'https://demo.velora.test/billing/success',
            'cancel_url'     => 'https://demo.velora.test/billing/expired',
            'amount'         => 49.00,
            'currency'       => 'USD',
        ];
    }
}
