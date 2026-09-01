<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Infrastructure\Payments\Moyasar\MoyasarWebhookProcessor;
use App\Services\MoyasarService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class MoyasarWebhookProcessingScenarioTest extends TestCase
{
    #[Test]
    public function paid_webhook_verifies_with_moyasar_api_and_activates_subscription(): void
    {
        config()->set('services.moyasar.webhook_secret', 'qa-secret');

        $tenantId = 'tenant-qa-moyasar-paid';
        $paymentId = 'pay_qa_paid_001';
        $planId = 1;

        DB::table('tenants')->insert([
            'id' => $tenantId,
            'data' => json_encode(['name' => 'Moyasar QA'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('subscription_plans')->insert([
            'id' => $planId,
            'name' => 'QA Monthly',
            'billing_cycle' => 'monthly',
            'price' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tenant_subscriptions')->insert([
            'tenant_id' => $tenantId,
            'status' => 'trial',
            'subscription_plan_id' => $planId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = Mockery::mock(MoyasarService::class);
        $service->shouldReceive('verifyPayment')
            ->once()
            ->with($paymentId)
            ->andReturn(['status' => 'paid']);
        $service->shouldReceive('activateSubscription')
            ->once()
            ->with($tenantId, $planId, 9900, $paymentId)
            ->andReturnUsing(function (string $tenant, int $plan, int $amount, string $payment): void {
                DB::table('tenant_subscriptions')
                    ->where('tenant_id', $tenant)
                    ->update([
                        'status' => 'active',
                        'subscription_plan_id' => $plan,
                        'amount_paid' => round($amount / 100, 2),
                        'payment_method' => 'moyasar',
                        'last_webhook_event' => 'moyasar_' . $payment,
                        'updated_at' => now(),
                    ]);
            });

        $processor = new MoyasarWebhookProcessor($service);
        $payload = json_encode([
            'id' => 'qa-moyasar-paid-event-001',
            'type' => 'payment.paid',
            'data' => [
                'id' => $paymentId,
                'status' => 'paid',
                'amount' => 9900,
                'metadata' => [
                    'tenant_id' => $tenantId,
                    'plan_id' => $planId,
                ],
            ],
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $payload, 'qa-secret');

        $result = $processor->process($payload, $signature);

        $this->assertSame(['status' => 'ok'], $result);
        $this->assertSame('active', DB::table('tenant_subscriptions')->where('tenant_id', $tenantId)->value('status'));
        $this->assertSame('moyasar_' . $paymentId, DB::table('tenant_subscriptions')->where('tenant_id', $tenantId)->value('last_webhook_event'));
    }

    #[Test]
    public function processing_failure_removes_unprocessed_event_so_the_provider_can_retry(): void
    {
        config()->set('services.moyasar.webhook_secret', 'qa-secret');

        $service = Mockery::mock(MoyasarService::class);
        $service->shouldReceive('verifyPayment')
            ->once()
            ->andThrow(new \RuntimeException('verification service unavailable'));

        $processor = new MoyasarWebhookProcessor($service);
        $payload = json_encode([
            'id' => 'qa-moyasar-retry-001',
            'type' => 'payment.paid',
            'data' => [
                'id' => 'pay_qa_retry_001',
                'status' => 'paid',
                'amount' => 5000,
                'metadata' => [
                    'tenant_id' => 'tenant-missing-but-valid-payload',
                    'plan_id' => 1,
                ],
            ],
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $payload, 'qa-secret');

        $this->expectException(\RuntimeException::class);

        try {
            $processor->process($payload, $signature);
        } finally {
            $this->assertDatabaseMissing('webhook_events', [
                'provider' => 'moyasar',
                'event_id' => 'qa-moyasar-retry-001',
            ]);
        }
    }
}
