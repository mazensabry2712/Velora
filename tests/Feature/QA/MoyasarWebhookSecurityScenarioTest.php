<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Infrastructure\Payments\Moyasar\MoyasarWebhookProcessor;
use App\Services\MoyasarService;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class MoyasarWebhookSecurityScenarioTest extends TestCase
{
    #[Test]
    public function missing_webhook_secret_is_rejected_before_any_ledger_or_payment_side_effect(): void
    {
        config()->set('services.moyasar.webhook_secret', '');

        $processor = new MoyasarWebhookProcessor(Mockery::mock(MoyasarService::class));
        $payload = json_encode([
            'id' => 'qa-moyasar-missing-secret-001',
            'type' => 'payment.paid',
            'data' => ['id' => 'pay_qa_001', 'status' => 'paid'],
        ], JSON_THROW_ON_ERROR);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Webhook secret is not configured');

        try {
            $processor->process($payload, null);
        } finally {
            $this->assertDatabaseMissing('webhook_events', [
                'provider' => 'moyasar',
                'event_id' => 'qa-moyasar-missing-secret-001',
            ]);
        }
    }

    #[Test]
    public function invalid_signature_is_rejected_before_the_event_is_recorded(): void
    {
        config()->set('services.moyasar.webhook_secret', 'qa-secret');

        $processor = new MoyasarWebhookProcessor(Mockery::mock(MoyasarService::class));
        $payload = json_encode([
            'id' => 'qa-moyasar-invalid-signature-001',
            'type' => 'payment.failed',
            'data' => ['id' => 'pay_qa_002', 'status' => 'failed'],
        ], JSON_THROW_ON_ERROR);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid signature');

        try {
            $processor->process($payload, 'bad-signature');
        } finally {
            $this->assertDatabaseMissing('webhook_events', [
                'provider' => 'moyasar',
                'event_id' => 'qa-moyasar-invalid-signature-001',
            ]);
        }
    }

    #[Test]
    public function valid_signature_records_and_processes_the_event_once(): void
    {
        config()->set('services.moyasar.webhook_secret', 'qa-secret');

        $moyasar = Mockery::mock(MoyasarService::class);
        $processor = new MoyasarWebhookProcessor($moyasar);
        $payload = json_encode([
            'id' => 'qa-moyasar-valid-001',
            'type' => 'payment.failed',
            'data' => ['id' => 'pay_qa_003', 'status' => 'failed'],
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $payload, 'qa-secret');

        $result = $processor->process($payload, $signature);

        $this->assertSame(['status' => 'ok'], $result);
        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'moyasar',
            'event_id' => 'qa-moyasar-valid-001',
            'event_type' => 'payment.failed',
        ]);
        $this->assertNotNull(
            DB::table('webhook_events')
                ->where('provider', 'moyasar')
                ->where('event_id', 'qa-moyasar-valid-001')
                ->value('processed_at')
        );
    }

    #[Test]
    public function duplicate_event_is_acknowledged_without_creating_a_second_ledger_record(): void
    {
        config()->set('services.moyasar.webhook_secret', 'qa-secret');

        $processor = new MoyasarWebhookProcessor(Mockery::mock(MoyasarService::class));
        $payload = json_encode([
            'id' => 'qa-moyasar-duplicate-001',
            'type' => 'payment.failed',
            'data' => ['id' => 'pay_qa_004', 'status' => 'failed'],
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $payload, 'qa-secret');

        $first = $processor->process($payload, $signature);
        $second = $processor->process($payload, $signature);

        $this->assertSame(['status' => 'ok'], $first);
        $this->assertSame(['status' => 'ok', 'duplicate' => true], $second);
        $this->assertSame(1, DB::table('webhook_events')
            ->where('provider', 'moyasar')
            ->where('event_id', 'qa-moyasar-duplicate-001')
            ->count());
    }
}
