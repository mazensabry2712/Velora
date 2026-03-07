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
use Illuminate\Support\Facades\Artisan;

/**
 * Integration tests for the Stripe webhook endpoint.
 *
 * Uses the central SQLite DB only — no tenant context needed.
 * Extends plain TestCase to avoid TenantTestCase re-wiring the 'sqlite' connection.
 */
#[Group('feature')]
#[Group('billing')]
#[Group('stripe')]
class StripeWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_URL = '/webhooks/stripe';

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Bind a mock StripeService that returns the provided Stripe\Event.
     */
    private function mockStripeEvent(\Stripe\Event $event): void
    {
        $mock = $this->mock(StripeService::class);
        $mock->shouldReceive('constructWebhookEvent')
             ->once()
             ->andReturn($event);
    }

    /**
     * Return a mock StripeService that throws SignatureVerificationException.
     */
    private function mockInvalidSignature(): void
    {
        $mock = $this->mock(StripeService::class);
        $mock->shouldReceive('constructWebhookEvent')
             ->once()
             ->andThrow(
                 \Stripe\Exception\SignatureVerificationException::factory('bad sig', 'payload', 'sig')
             );
    }

    /**
     * Build a minimal \Stripe\Event from an array of data.
     */
    private function buildStripeEvent(string $type, array $dataObject, string $id = 'evt_test_123'): \Stripe\Event
    {
        return \Stripe\Event::constructFrom([
            'id'      => $id,
            'type'    => $type,
            'object'  => 'event',
            'data'    => ['object' => $dataObject],
        ]);
    }

    /**
     * Create a tenant row in the central DB with name/email in JSON data column.
     */
    private function createTenantRow(string $tenantId, string $email, string $name): void
    {
        DB::table('tenants')->insert([
            'id'         => $tenantId,
            'data'       => json_encode(['name' => $name, 'email' => $email]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Insert a minimal subscription_plans row and a tenant_subscriptions row.
     */
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

    // ── Signature / Auth ─────────────────────────────────────────────────

    #[Test]
    public function missing_signature_header_returns_400(): void
    {
        // StripeService is constructor-injected — mock it so it resolves without a real API key.
        $this->mock(StripeService::class); // allows injection, no methods expected

        $response = $this->postJson(self::WEBHOOK_URL, ['type' => 'ping'], [
            // No Stripe-Signature header
        ]);

        $response->assertStatus(400)
                 ->assertJson(['error' => 'Missing signature']);
    }

    #[Test]
    public function invalid_stripe_signature_returns_400(): void
    {
        $this->mockInvalidSignature();

        $response = $this->postJson(self::WEBHOOK_URL, ['type' => 'ping'], [
            'Stripe-Signature' => 'bad_signature_value',
        ]);

        $response->assertStatus(400)
                 ->assertJson(['error' => 'Invalid signature']);
    }

    // ── invoice.payment_failed ────────────────────────────────────────────

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
        ]);

        $this->mockStripeEvent($event);

        $response = $this->postJson(self::WEBHOOK_URL, [], [
            'Stripe-Signature' => 'valid_but_mocked',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok']);

        // Email was queued to the tenant owner
        Mail::assertQueued(PaymentFailedMail::class, function (PaymentFailedMail $mail) {
            return $mail->hasTo('owner@salon.com')
                && $mail->invoiceId === 'in_test_abc123';
        });

        // Subscription status changed to grace with a grace_ends_at timestamp
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
            'subscription_details' => null,  // no metadata
        ]);

        $this->mockStripeEvent($event);

        $response = $this->postJson(self::WEBHOOK_URL, [], [
            'Stripe-Signature' => 'valid_but_mocked',
        ]);

        $response->assertStatus(200);

        // No email sent
        Mail::assertNothingQueued();
    }

    // ── checkout.session.completed ────────────────────────────────────────

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
        ]);

        $this->mockStripeEvent($event);

        $response = $this->postJson(self::WEBHOOK_URL, [], [
            'Stripe-Signature' => 'valid_but_mocked',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok']);

        $sub = DB::table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->first();

        $this->assertNotNull($sub->converted_at, 'converted_at should be set after checkout');
    }

    #[Test]
    public function checkout_completed_without_tenant_id_returns_ok(): void
    {
        $event = $this->buildStripeEvent('checkout.session.completed', [
            'id'       => 'cs_no_tenant',
            'object'   => 'checkout.session',
            'metadata' => [],  // no tenant_id
        ]);

        $this->mockStripeEvent($event);

        $response = $this->postJson(self::WEBHOOK_URL, [], [
            'Stripe-Signature' => 'valid_but_mocked',
        ]);

        $response->assertStatus(200);
    }

    // ── Unknown events ────────────────────────────────────────────────────

    #[Test]
    public function unknown_event_type_returns_ok_and_is_ignored(): void
    {
        $event = $this->buildStripeEvent('some.unknown.event', [
            'id'     => 'obj_abc',
            'object' => 'unknown',
        ]);

        $this->mockStripeEvent($event);

        $response = $this->postJson(self::WEBHOOK_URL, [], [
            'Stripe-Signature' => 'valid_but_mocked',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok']);
    }
}
