<?php

namespace Tests\Unit\Payments;

use App\Payments\Gateways\PaymobGateway;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests for PaymobGateway.
 *
 * All HTTP calls are faked using Http::fake() — no live API calls.
 */
#[Group('unit')]
#[Group('payments')]
#[Group('paymob')]
class PaymobGatewayTest extends TestCase
{
    // ── createCheckout ─────────────────────────────────────────────────

    #[Test]
    public function create_checkout_runs_three_step_flow_and_returns_iframe_url(): void
    {
        Http::fake([
            // Step 1: auth
            'accept.paymob.com/api/auth/tokens'              => Http::response(['token' => 'tk_auth_abc'], 200),
            // Step 2: create order
            'accept.paymob.com/api/ecommerce/orders'         => Http::response(['id' => 99999], 200),
            // Step 3: payment key
            'accept.paymob.com/api/acceptance/payment_keys'  => Http::response(['token' => 'pmk_pay_token'], 200),
        ]);

        config([
            'services.paymob.api_key'        => 'test_api_key',
            'services.paymob.integration_id' => '12345',
            'services.paymob.iframe_id'       => '67890',
        ]);

        $gateway = new PaymobGateway();

        $url = $gateway->createCheckout([
            'plan_id'        => 1,
            'tenant_id'      => 'tenant-eg-001',
            'customer_email' => 'test@salon.eg',
            'customer_name'  => 'Ahmed Ali',
            'amount'         => 150.00,
            'currency'       => 'EGP',
        ]);

        $this->assertEquals(
            'https://accept.paymob.com/api/acceptance/iframes/67890?payment_token=pmk_pay_token',
            $url
        );

        // Ensure auth was called with the correct API key
        Http::assertSent(function (HttpRequest $request) {
            return str_contains($request->url(), '/auth/tokens')
                && $request->data()['api_key'] === 'test_api_key';
        });

        // Ensure order was created with correct piasters amount (150 × 100)
        Http::assertSent(function (HttpRequest $request) {
            return str_contains($request->url(), '/ecommerce/orders')
                && $request->data()['amount_cents'] === 15000;
        });

        // Ensure payment key request used the order ID
        Http::assertSent(function (HttpRequest $request) {
            return str_contains($request->url(), '/acceptance/payment_keys')
                && $request->data()['order_id'] === 99999;
        });
    }

    #[Test]
    public function create_checkout_throws_when_auth_fails(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $gateway = new PaymobGateway();

        $this->expectException(\Exception::class);

        $gateway->createCheckout([
            'plan_id'        => 1,
            'tenant_id'      => 'tenant-eg-001',
            'customer_email' => 'test@salon.eg',
            'customer_name'  => 'Test User',
            'amount'         => 100.00,
            'currency'       => 'EGP',
        ]);
    }

    // ── verifyPayment ──────────────────────────────────────────────────

    #[Test]
    public function verify_payment_returns_true_when_success_is_true_and_hmac_valid(): void
    {
        config(['services.paymob.hmac_secret' => 'test_secret']);

        $payload = $this->buildValidPaymobPayload(success: true, secret: 'test_secret');

        $gateway = new PaymobGateway();

        $this->assertTrue($gateway->verifyPayment($payload));
    }

    #[Test]
    public function verify_payment_returns_false_when_hmac_mismatch(): void
    {
        config(['services.paymob.hmac_secret' => 'test_secret']);

        $payload         = $this->buildValidPaymobPayload(success: true, secret: 'test_secret');
        $payload['hmac'] = 'invalid_hmac_value_xyz';

        $gateway = new PaymobGateway();

        $this->assertFalse($gateway->verifyPayment($payload));
    }

    #[Test]
    public function verify_payment_returns_false_when_success_is_false(): void
    {
        config(['services.paymob.hmac_secret' => 'test_secret']);

        $payload = $this->buildValidPaymobPayload(success: false, secret: 'test_secret');

        $gateway = new PaymobGateway();

        $this->assertFalse($gateway->verifyPayment($payload));
    }

    #[Test]
    public function verify_payment_returns_false_when_hmac_missing(): void
    {
        $gateway = new PaymobGateway();

        $this->assertFalse($gateway->verifyPayment(['success' => 'true']));
    }

    // ── refund ─────────────────────────────────────────────────────────

    #[Test]
    public function refund_returns_true_on_successful_api_call(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens'                    => Http::response(['token' => 'tk_auth'], 200),
            'accept.paymob.com/api/acceptance/void_refund/refund'  => Http::response(['id' => 1], 200),
        ]);

        $gateway = new PaymobGateway();

        $this->assertTrue($gateway->refund('txn_paymob_001'));
    }

    #[Test]
    public function refund_returns_false_when_api_call_fails(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens'                    => Http::response(['token' => 'tk_auth'], 200),
            'accept.paymob.com/api/acceptance/void_refund/refund'  => Http::response(['error' => 'not found'], 404),
        ]);

        $gateway = new PaymobGateway();

        $this->assertFalse($gateway->refund('txn_not_found'));
    }

    // ── Helpers ────────────────────────────────────────────────────────

    /**
     * Build a Paymob callback payload with a valid HMAC.
     * Mirrors the HMAC fields defined in PaymobGateway::computeHmac().
     */
    private function buildValidPaymobPayload(bool $success, string $secret): array
    {
        $successStr = $success ? 'true' : 'false';
        $fields     = [
            'amount_cents'           => '15000',
            'created_at'             => '2026-03-08T10:00:00',
            'currency'               => 'EGP',
            'error_occured'          => 'false',
            'has_parent_transaction' => 'false',
            'id'                     => '12345',
            'integration_id'         => '12345',
            'is_3d_secure'           => 'false',
            'is_auth'                => 'false',
            'is_capture'             => 'false',
            'is_refunded'            => 'false',
            'is_standalone_payment'  => 'true',
            'is_voided'              => 'false',
            'order'                  => '99999',
            'owner'                  => '111',
            'pending'                => 'false',
            'source_data_pan'        => '1234',
            'source_data_sub_type'   => 'MasterCard',
            'source_data_type'       => 'card',
            'success'                => $successStr,
        ];

        $hmacFieldOrder = [
            'amount_cents', 'created_at', 'currency', 'error_occured',
            'has_parent_transaction', 'id', 'integration_id', 'is_3d_secure',
            'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
            'is_voided', 'order', 'owner', 'pending', 'source_data_pan',
            'source_data_sub_type', 'source_data_type', 'success',
        ];

        $concatenated = implode('', array_map(fn ($f) => $fields[$f] ?? '', $hmacFieldOrder));
        $hmac         = hash_hmac('sha512', $concatenated, $secret);

        return array_merge($fields, ['hmac' => $hmac]);
    }
}
