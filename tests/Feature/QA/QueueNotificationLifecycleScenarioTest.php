<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Domain\Queue\Events\QueueLifecycleNotificationRequested;
use App\Infrastructure\Notifications\Listeners\CreateQueueLifecycleNotificationDeliveries;
use App\Jobs\SendQueueLifecycleNotification;
use App\Models\Appointment;
use App\Models\NotificationDelivery;
use App\Models\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue as QueueFake;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class QueueNotificationLifecycleScenarioTest extends TenantTestCase
{
    #[Test]
    public function queue_status_change_dispatches_the_canonical_lifecycle_event(): void
    {
        Event::fake([QueueLifecycleNotificationRequested::class]);

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => today()->toDateString(),
            'time_slot' => '14:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'A030',
            'queue_date' => today()->toDateString(),
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $queue->update(['status' => 'serving']);

        Event::assertDispatched(QueueLifecycleNotificationRequested::class, function ($event) use ($queue, $appointment): bool {
            return $event->queueId === $queue->id
                && $event->appointmentId === $appointment->id
                && $event->event === 'queue.turn_now'
                && $event->updateType === 'next'
                && $event->queueNumber === 'A030';
        });
    }

    #[Test]
    public function lifecycle_listener_persists_one_delivery_per_available_channel_and_dispatches_jobs(): void
    {
        QueueFake::fake();

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => today()->toDateString(),
            'time_slot' => '14:30',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'A031',
            'queue_date' => today()->toDateString(),
            'status' => 'serving',
            'is_vip' => false,
        ]);

        $event = new QueueLifecycleNotificationRequested(
            tenantId: (string) $this->tenant->getKey(),
            queueId: $queue->id,
            appointmentId: $appointment->id,
            publicReference: (string) $appointment->public_reference,
            event: 'queue.turn_now',
            updateType: 'next',
            queueNumber: (string) $queue->queue_number,
            position: null,
            oldPosition: 1,
            customerType: 'user',
            customerId: $this->customer->id,
            customerName: $this->customer->name,
            email: $this->customer->email,
            phone: $this->customer->phone,
            locale: 'ar',
            eventId: 'qa-queue-event-001',
        );

        $listener = app(CreateQueueLifecycleNotificationDeliveries::class);
        $listener->handle($event);

        $this->assertDatabaseHas('notification_deliveries', [
            'appointment_id' => $appointment->id,
            'event' => 'queue.turn_now',
            'channel' => 'email',
            'recipient' => $this->customer->email,
            'status' => 'queued',
        ]);

        $this->assertDatabaseHas('notification_deliveries', [
            'appointment_id' => $appointment->id,
            'event' => 'queue.turn_now',
            'channel' => 'whatsapp',
            'recipient' => $this->customer->phone,
            'status' => 'queued',
        ]);

        $this->assertSame(
            2,
            NotificationDelivery::query()
                ->where('appointment_id', $appointment->id)
                ->where('event', 'queue.turn_now')
                ->count(),
        );

        QueueFake::assertPushed(SendQueueLifecycleNotification::class, 2);
    }
}
