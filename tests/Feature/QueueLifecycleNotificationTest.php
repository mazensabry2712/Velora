<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Queue\Events\QueueLifecycleNotificationRequested;
use App\Infrastructure\Notifications\Listeners\CreateQueueLifecycleNotificationDeliveries;
use App\Jobs\SendQueueLifecycleNotification;
use App\Mail\QueueLifecycleNotificationMail;
use App\Models\Appointment;
use App\Models\NotificationDelivery;
use App\Models\Queue;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue as QueueBus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class QueueLifecycleNotificationTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // TenantTestCase clears Eloquent booted models during teardown. Re-register
        // the production observer for each test so this feature test remains
        // isolated without depending on static model state from another test.
        Queue::observe(\App\Observers\QueueObserver::class);
    }

    #[Test]
    public function waiting_to_serving_emits_turn_now(): void
    {
        Event::fake([QueueLifecycleNotificationRequested::class]);

        $first = $this->makeQueue('A001');
        $second = $this->makeQueue('A002');

        $second->update(['status' => 'serving']);

        Event::assertDispatched(QueueLifecycleNotificationRequested::class, function (QueueLifecycleNotificationRequested $event) use ($second): bool {
            return $event->event === 'queue.turn_now'
                && $event->updateType === 'next'
                && $event->queueId === $second->id
                && $event->appointmentId === $second->appointment_id
                && $event->publicReference === $second->appointment->public_reference
                && $event->oldPosition === 2
                && $event->position === null;
        });

        self::assertSame('waiting', $first->refresh()->status);
    }

    #[Test]
    public function queue_advance_emits_almost_turn_for_new_position_one_and_position_update_for_others(): void
    {
        Event::fake([QueueLifecycleNotificationRequested::class]);

        $one = $this->makeQueue('A001');
        $two = $this->makeQueue('A002');
        $three = $this->makeQueue('A003');

        $one->update(['status' => 'serving']);

        Event::assertDispatchedTimes(QueueLifecycleNotificationRequested::class, 3);
        Event::assertDispatched(QueueLifecycleNotificationRequested::class, function (QueueLifecycleNotificationRequested $event) use ($one): bool {
            return $event->event === 'queue.turn_now' && $event->queueId === $one->id;
        });
        Event::assertDispatched(QueueLifecycleNotificationRequested::class, function (QueueLifecycleNotificationRequested $event) use ($two): bool {
            return $event->event === 'queue.almost_turn'
                && $event->updateType === 'ready'
                && $event->queueId === $two->id
                && $event->oldPosition === 2
                && $event->position === 1;
        });
        Event::assertDispatched(QueueLifecycleNotificationRequested::class, function (QueueLifecycleNotificationRequested $event) use ($three): bool {
            return $event->event === 'queue.position_changed'
                && $event->updateType === 'position_update'
                && $event->queueId === $three->id
                && $event->oldPosition === 3
                && $event->position === 2;
        });
    }

    #[Test]
    public function vip_insertion_notifies_only_existing_entries_that_shift(): void
    {
        Event::fake([QueueLifecycleNotificationRequested::class]);

        $one = $this->makeQueue('A001');
        $two = $this->makeQueue('A002');

        $vip = $this->makeQueue('A003', true);

        Event::assertDispatchedTimes(QueueLifecycleNotificationRequested::class, 2);
        Event::assertDispatched(QueueLifecycleNotificationRequested::class, function (QueueLifecycleNotificationRequested $event) use ($one): bool {
            return $event->event === 'queue.position_changed'
                && $event->queueId === $one->id
                && $event->oldPosition === 1
                && $event->position === 2;
        });
        Event::assertDispatched(QueueLifecycleNotificationRequested::class, function (QueueLifecycleNotificationRequested $event) use ($two): bool {
            return $event->event === 'queue.position_changed'
                && $event->queueId === $two->id
                && $event->oldPosition === 2
                && $event->position === 3;
        });

        self::assertDatabaseHas('queues', [
            'id' => $vip->id,
            'is_vip' => 1,
            'status' => 'waiting',
        ]);
    }

    #[Test]
    public function delivery_listener_creates_email_and_whatsapp_once(): void
    {
        QueueBus::fake();

        $queue = $this->makeQueue('A001');
        $event = $this->eventFor($queue, 'queue.position_changed', 'position_update', 2, 3);

        $listener = app(CreateQueueLifecycleNotificationDeliveries::class);
        $listener->handle($event);
        $listener->handle($event);

        self::assertSame(2, NotificationDelivery::query()->count());
        self::assertDatabaseHas('notification_deliveries', [
            'event' => 'queue.position_changed',
            'channel' => 'email',
            'dedupe_key' => 'queue.position_changed|email|' . $queue->appointment->public_reference . '|' . $event->eventId,
            'status' => 'queued',
        ]);
        self::assertDatabaseHas('notification_deliveries', [
            'event' => 'queue.position_changed',
            'channel' => 'whatsapp',
            'dedupe_key' => 'queue.position_changed|whatsapp|' . $queue->appointment->public_reference . '|' . $event->eventId,
            'status' => 'queued',
        ]);

        QueueBus::assertPushed(SendQueueLifecycleNotification::class, 2);
    }

    #[Test]
    public function email_job_marks_delivery_sent(): void
    {
        Mail::fake();

        $queue = $this->makeQueue('A001');
        $event = $this->eventFor($queue, 'queue.almost_turn', 'ready', 1, 2);

        $listener = app(CreateQueueLifecycleNotificationDeliveries::class);
        $listener->handle($event);

        $delivery = NotificationDelivery::query()->where('channel', 'email')->firstOrFail();

        $job = new SendQueueLifecycleNotification(
            tenant: $this->tenant,
            deliveryId: (int) $delivery->id,
            data: [
                'event_id' => $event->eventId,
                'queue_id' => $queue->id,
                'appointment_id' => $queue->appointment_id,
                'public_reference' => $queue->appointment->public_reference,
                'event' => $event->event,
                'update_type' => 'ready',
                'queue_number' => $queue->queue_number,
                'position' => 1,
                'old_position' => 2,
                'customer_type' => 'user',
                'customer_id' => $this->customer->id,
                'customer_name' => $this->customer->name,
                'recipient' => $this->customer->email,
                'locale' => 'en',
                'channel' => 'email',
            ],
        );

        $job->handle(app(\App\Domain\Notifications\Contracts\WhatsAppProvider::class));

        $delivery->refresh();
        self::assertSame('sent', $delivery->status);
        self::assertSame(1, $delivery->attempts);
        self::assertNotNull($delivery->sent_at);
        Mail::assertSent(QueueLifecycleNotificationMail::class);
    }

    #[Test]
    public function unconfigured_whatsapp_provider_marks_delivery_skipped_without_fake_success(): void
    {
        $queue = $this->makeQueue('A001');
        $event = $this->eventFor($queue, 'queue.turn_now', 'next', null, 1);

        $listener = app(CreateQueueLifecycleNotificationDeliveries::class);
        $listener->handle($event);

        $delivery = NotificationDelivery::query()->where('channel', 'whatsapp')->firstOrFail();

        $job = new SendQueueLifecycleNotification(
            tenant: $this->tenant,
            deliveryId: (int) $delivery->id,
            data: [
                'event_id' => $event->eventId,
                'queue_id' => $queue->id,
                'appointment_id' => $queue->appointment_id,
                'public_reference' => $queue->appointment->public_reference,
                'event' => $event->event,
                'update_type' => 'next',
                'queue_number' => $queue->queue_number,
                'position' => null,
                'old_position' => 1,
                'customer_type' => 'user',
                'customer_id' => $this->customer->id,
                'customer_name' => $this->customer->name,
                'recipient' => $this->customer->phone,
                'locale' => 'en',
                'channel' => 'whatsapp',
            ],
        );

        $job->handle(app(\App\Domain\Notifications\Contracts\WhatsAppProvider::class));

        $delivery->refresh();
        self::assertSame('skipped', $delivery->status);
        self::assertNull($delivery->sent_at);
        self::assertSame('skipped', $delivery->metadata['provider_status']);
        self::assertNotEmpty($delivery->metadata['provider_reason']);
    }

    #[Test]
    public function failed_job_records_final_failure(): void
    {
        $queue = $this->makeQueue('A001');
        $event = $this->eventFor($queue, 'queue.turn_now', 'next', null, 1);
        $delivery = NotificationDelivery::create([
            'appointment_id' => $queue->appointment_id,
            'public_reference' => $queue->appointment->public_reference,
            'event' => $event->event,
            'channel' => 'email',
            'recipient' => $this->customer->email,
            'provider' => 'mail',
            'status' => 'queued',
            'attempts' => 3,
            'dedupe_key' => $event->event . '|email|' . $queue->appointment->public_reference,
            'queued_at' => now(),
            'metadata' => ['event_id' => $event->eventId],
        ]);

        $job = new SendQueueLifecycleNotification(
            tenant: $this->tenant,
            deliveryId: (int) $delivery->id,
            data: ['channel' => 'email'],
        );

        $job->failed(new \RuntimeException('queue provider failed'));

        $delivery->refresh();
        self::assertSame('failed', $delivery->status);
        self::assertNotNull($delivery->failed_at);
        self::assertSame('queue provider failed', $delivery->last_error);
    }

    private function makeAppointment(): Appointment
    {
        return Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'public_reference' => 'VL-QN-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'date' => now()->toDateString(),
            'time_slot' => now()->format('H:i'),
            'starts_at' => now()->addMinutes(30),
            'ends_at' => now()->addMinutes(60),
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => 'test',
        ]);
    }

    private function makeQueue(string $queueNumber, bool $vip = false): Queue
    {
        return Queue::create([
            'appointment_id' => $this->makeAppointment()->id,
            'queue_number' => $queueNumber,
            'queue_date' => now()->toDateString(),
            'status' => 'waiting',
            'is_vip' => $vip,
        ]);
    }

    private function eventFor(
        Queue $queue,
        string $eventName,
        string $updateType,
        ?int $position,
        ?int $oldPosition,
    ): QueueLifecycleNotificationRequested {
        $customer = $queue->appointment->customer;

        return new QueueLifecycleNotificationRequested(
            tenantId: (string) $this->tenant->getKey(),
            queueId: $queue->id,
            appointmentId: $queue->appointment_id,
            publicReference: $queue->appointment->public_reference,
            event: $eventName,
            updateType: $updateType,
            queueNumber: (string) $queue->queue_number,
            position: $position,
            oldPosition: $oldPosition,
            customerType: 'user',
            customerId: (int) $customer->id,
            customerName: $customer->name,
            email: $customer->email,
            phone: $customer->phone,
            locale: $customer->locale ?: 'en',
            eventId: (string) \Illuminate\Support\Str::uuid(),
        );
    }
}
