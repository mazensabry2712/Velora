<?php

namespace Tests\Feature\Billing;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Services\StripeService;
use App\Mail\PaymentFailedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

/**
 * Integration tests for the Stripe webhook endpoint.
 *
 * Uses the central SQLite DB only — no tenant context needed.
 */
#[Group('feature')]
#[Group('billing')]
#[Group('stripe')]
class StripeWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_URL = '/webhooks/stripe';

    private function mockStripeEvent(\Stripe\Event $event): void
    {
        $mock = $this->mock(StripeService::class);
        $mock->shouldReceive('constructWebhookEvent')
             ->once()
             ->andReturn($event);
    }

    private function mockInvalidSignature(): void
    {
        $mock = $this->mock(StripeService::class);
        $mock->shouldReceive('constructWebhookEvent')
             ->once()
             ->andThrow(
                 \Stripe\Exception\SignatureVerificationException::factory('bad sig', 'payload', 'sig')
             );
    }

    private function buildStripeEvent(string $type, array $dataObject, string $id = 'evt_test_123'): \Stripe\Event
    {
        return \Stripe\Event::constructFrom([
            'id'      => $id,
            'type'    => $type,
            'object'  => 'event',
            'data'    => ['object' => $dataObject],
        ]);
    }

    private function createTenantRow(string $tenantId, string $email, string $name): void
    {
        DB::table('tenants')->insert([
            'id'         => $tenantId,
            'data'       => json_encode(['name' => $name, 'email' => $email]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertSubscription(string $tenantId, array $attrs = []): void
    {
        $planId = DB::table('subscription_plans')->value('id');
        if (! $planId) {
            $planId = DB::table('subscription_plans')->insertGetId([
                'name'          => 'Starter',
                'slug'          => 'starter',
                'price'         => 99.00,
                'billing_cycle' => 'monthly',
                'is_active'     => 1,
                'is_popular'    => 0,
                'trial_days'    => 14,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        DB::table('tenant_subscriptions')->insert(array_merge([
            'tenant_id'            => $tenantId,
            'subscription_plan_id' => $planId,
            'status'               => 'active',
            'amount_paid'          => 99,
            'created_at'           => now(),
            'updated_at'           => now(),
        ], $attrs));
    }

    #[Test]
    public function missing_signature_header_returns_400(): void
    {
        $this->mock(StripeService::class);

        $response = $this->postJson(self::WEBHOOK_URL, []);

        $response->assertStatus(400)
                 ->assertJson(['error' => 'Missing signature']);
    }

    #[Test]
    public function invalid_stripe_signature_returns_400(): void
    {
        $this->mockInvalidSignature();

        $response = $this->postJson(self::WEBHOOK_URL, [], [
            'Stripe-Signature' => 'bad_signature_value',
        ]);

        $response->assertStatus(400)
                 ->assertJson(['error' => 'Invalid signature']);
    }

    #[Test]
    public function payment_failed_sends_email_and_sets_grace_period(): void
    {
        Mail::fake();

        $tenantId = 'tenant-stripe-test-1';
        $this->createTenantRow($tenantId, 'owner@salon.com', 'My Salon');
        $this->insertSubscription($tenantId, ['status' => 'active']);

        $event = $this->buildStripeEvent('invoice.payment_failed', [
            'id'                   => 'in_test_abc123',
            'object'               => 'invoice',
            'subscription_details' => [
                'metadata' => ['tenant_id' => $tenantId],
            ],
        ], 'evt_payment_failed_1');

        $this->mockStripeEvent($event);

        $response = $this->postJson(self::WEBHOOK_URL, [], [
            'Stripe-Signature' => 'valid_but_mocked',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok']);

        Mail::assertQueued(PaymentFailedMail::class, function (PaymentFailedMail $mail) {
            return $mail->hasTo('owner@salon.com')
                && $mail->invoiceId === 'in_test_abc123';
        });

        $sub = DB::table('tenant_subscriptions')
                  ->where('tenant_id', $tenantId)
                  ->first();

        $this->assertEquals('grace', $sub->status);
        $this->assertNotNull($sub->grace_ends_at);
    }

    #[Test]
    public function payment_failed_without_tenant_id_metadata_returns_ok_silently(): void
    {
        Mail::fake();

        $event = $this->buildStripeEvent('invoice.payment_failed', [
            'id'                   => 'in_no_tenant',
            'object'               => 'invoice',
            'subscription_details' => null,
        ], 'evt_payment_failed_missing_tenant');

        $this->mockStripeEvent($event);

        $response = $this->postJson(self::WEBHOOK_URL, [], [
            'Stripe-Signature' => 'valid_but_mocked',
        ]);

        $response->assertStatus(200);
        Mail::assertNothingQueued();
    }

    #[Test]
    public function checkout_completed_stamps_converted_at(): void
    {
        $tenantId = 'tenant-stripe-test-2';
        $this->createTenantRow($tenantId, 'owner@salon.com', 'Test Salon');
        $this->insertSubscription($tenantId, [
            'status'       => 'trial',
            'converted_at' => null,
        ]);

        $event = $this->buildStripeEvent('checkout.session.completed', [
            'id'       => 'cs_test_xyz',
            'object'   => 'checkout.session',
            'metadata' => ['tenant_id' => $tenantId],
        ], 'evt_checkout_1');

        $this->mockStripeEvent($event);

        $response = $this->postJson(self::WEBHOOK_URL, [], [
            'Stripe-Signature' => 'valid_but_mocked',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok']);

        $sub = DB::table('tenant_subscriptions')->where('tenant_id', $tenantId)->first();
        $this->assertNotNull($sub->converted_at);
    }

    #[Test]
    public function duplicate_checkout_event_is_ignored_without_repeating_side_effect(): void
    {
        $tenantId = 'tenant-stripe-test-duplicate';
        $this->createTenantRow($tenantId, 'owner@salon.com', 'Duplicate Test Salon');
        $this->insertSubscription($tenantId, [
            'status' => 'trial',
            'converted_at' => null,
        ]);

        $event = $this->buildStripeEvent('checkout.session.completed', [
            'id'       => 'cs_duplicate',
            'object'   => 'checkout.session',
            'metadata' => ['tenant_id' => $tenantId],
        ], 'evt_duplicate_checkout');

        $this->mockStripeEvent($event);
        $first = $this->postJson(self::WEBHOOK_URL, [], [
            'Stripe-Signature' => 'valid_but_mocked',
        ]);
        $first->assertOk()->assertJson(['status' => 'ok']);

        $convertedAt = DB::table('tenant_subscriptions')->where('tenant_id', $tenantId)->value('converted_at');
        $this->assertNotNull($convertedAt);

        $this->mockStripeEvent($event);
        $second = $this->postJson(self::WEBHOOK_URL, [], [
            'Stripe-Signature' => 'valid_but_mocked',
        ]);
        $second->assertOk()->assertJson(['status' => 'ok', 'duplicate' => true]);

        $this->assertSame($convertedAt, DB::table('tenant_subscriptions')->where('tenant_id', $tenantId)->value('converted_at'));
        $this->assertSame(1, DB::table('webhook_events')->where('provider', 'stripe')->where('event_id', 'evt_duplicate_checkout')->count());
    }

    #[Test]
    public function checkout_completed_without_tenant_id_returns_ok(): void
    {
        $event = $this->buildStripeEvent('checkout.session.completed', [
            'id'       => 'cs_no_tenant',
            'object'   => 'checkout.session',
            'metadata' => [],
        ], 'evt_checkout_no_tenant');

        $this->mockStripeEvent($event);

        $response = $this->postJson(self::WEBHOOK_URL, [], [
            'Stripe-Signature' => 'valid_but_mocked',
        ]);

        $response->assertStatus(200);
    }

    #[Test]
    public function unknown_event_type_returns_ok_and_is_ignored(): void
    {
        $event = $this->buildStripeEvent('some.unknown.event', [
            'id'     => 'obj_abc',
            'object' => 'unknown',
        ], 'evt_unknown_1');

        $this->mockStripeEvent($event);

        $response = $this->postJson(self::WEBHOOK_URL, [], [
            'Stripe-Signature' => 'valid_but_mocked',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok']);
    }
}
