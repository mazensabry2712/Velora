<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Domain\Notifications\Contracts\WhatsAppProvider;
use App\Domain\Notifications\Contracts\WhatsAppSendResult;
use App\Jobs\SendQueueLifecycleNotification;
use App\Models\Appointment;
use App\Models\NotificationDelivery;
use App\Models\Queue;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class NotificationDeliveryRecoveryScenarioTest extends TenantTestCase
{
    #[Test]
    public function successful_email_delivery_is_marked_sent_and_records_attempt(): void
    {
        Mail::fake();

        [$appointment, $queue] = $this->createServingQueue();

        $delivery = NotificationDelivery::create([
            'appointment_id' => $appointment->id,
            'public_reference' => $appointment->public_reference,
            'event' => 'queue.turn_now',
            'channel' => 'email',
            'recipient' => $this->customer->email,
            'provider' => 'mail',
            'status' => 'queued',
            'attempts' => 0,
            'dedupe_key' => 'qa-recovery-email-' . $appointment->id,
            'queued_at' => now(),
        ]);

        $job = new SendQueueLifecycleNotification(
            tenant: $this->tenant,
            deliveryId: $delivery->id,
            data: [
                'event_id' => 'qa-event-email-001',
                'queue_id' => $queue->id,
                'appointment_id' => $appointment->id,
                'public_reference' => $appointment->public_reference,
                'event' => 'queue.turn_now',
                'update_type' => 'next',
                'queue_number' => $queue->queue_number,
                'position' => null,
                'old_position' => 1,
                'customer_type' => 'user',
                'customer_id' => $this->customer->id,
                'customer_name' => $this->customer->name,
                'recipient' => $this->customer->email,
                'locale' => 'ar',
                'channel' => 'email',
            ],
        );

        $job->handle(Mockery::mock(WhatsAppProvider::class));

        $delivery->refresh();

        $this->assertSame('sent', $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNotNull($delivery->sent_at);
        $this->assertNull($delivery->last_error);
        Mail::assertSent(\App\Mail\QueueLifecycleNotificationMail::class);
    }

    #[Test]
    public function failed_whatsapp_delivery_is_requeued_and_failed_callback_marks_terminal_failure(): void
    {
        [$appointment, $queue] = $this->createServingQueue();

        $delivery = NotificationDelivery::create([
            'appointment_id' => $appointment->id,
            'public_reference' => $appointment->public_reference,
            'event' => 'queue.turn_now',
            'channel' => 'whatsapp',
            'recipient' => $this->customer->phone,
            'provider' => 'whatsapp',
            'status' => 'queued',
            'attempts' => 0,
            'dedupe_key' => 'qa-recovery-whatsapp-' . $appointment->id,
            'queued_at' => now(),
        ]);

        $provider = Mockery::mock(WhatsAppProvider::class);
        $provider->shouldReceive('send')
            ->once()
            ->andReturn(WhatsAppSendResult::failed('provider timeout'));

        $job = new SendQueueLifecycleNotification(
            tenant: $this->tenant,
            deliveryId: $delivery->id,
            data: [
                'event_id' => 'qa-event-whatsapp-001',
                'queue_id' => $queue->id,
                'appointment_id' => $appointment->id,
                'public_reference' => $appointment->public_reference,
                'event' => 'queue.turn_now',
                'update_type' => 'next',
                'queue_number' => $queue->queue_number,
                'position' => null,
                'old_position' => 1,
                'customer_type' => 'user',
                'customer_id' => $this->customer->id,
                'customer_name' => $this->customer->name,
                'recipient' => $this->customer->phone,
                'locale' => 'ar',
                'channel' => 'whatsapp',
            ],
        );

        try {
            $job->handle($provider);
            $this->fail('Expected the WhatsApp provider failure to propagate from the job.');
        } catch (RuntimeException $exception) {
            $this->assertSame('provider timeout', $exception->getMessage());
        }

        $delivery->refresh();
        $this->assertSame('queued', $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertSame('provider timeout', $delivery->last_error);
        $this->assertNull($delivery->failed_at);

        $job->failed(new RuntimeException('provider timeout'));

        $delivery->refresh();
        $this->assertSame('failed', $delivery->status);
        $this->assertNotNull($delivery->failed_at);
        $this->assertSame('provider timeout', $delivery->last_error);
    }

    /** @return array{0: Appointment, 1: Queue} */
    private function createServingQueue(): array
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => today()->toDateString(),
            'time_slot' => '16:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'A090',
            'queue_date' => today()->toDateString(),
            'status' => 'serving',
            'is_vip' => false,
        ]);

        return [$appointment, $queue];
    }
}
