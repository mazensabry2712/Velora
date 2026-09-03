<?php

namespace Tests\Feature\QA;

use App\Application\Queue\Actions\CallNextQueueEntry;
use App\Application\Queue\Actions\TransitionQueueEntry;
use App\Domain\Queue\Contracts\QueueReader;
use App\Models\Appointment;
use App\Models\Queue;
use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class QueueLifecycleScenarioTest extends TenantTestCase
{
    #[Test]
    public function future_queue_is_read_by_business_date_not_creation_timestamp(): void
    {
        $timezone = $this->staff->timezone ?: config('app.timezone');
        $appointmentDate = now($timezone)->addDay()->toDateString();

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => $appointmentDate,
            'time_slot' => '09:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'A001',
            'queue_date' => $appointmentDate,
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $reader = app(QueueReader::class);
        $queues = $reader->forDate($appointmentDate);
        $this->assertTrue($queues->contains('id', $queue->id));
        $status = $reader->status('A001', $appointmentDate);
        $this->assertSame($queue->id, $status['queue']?->id);
    }

    #[Test]
    public function call_next_does_not_consume_a_future_queue_entry(): void
    {
        $today = today()->toDateString();
        $tomorrow = today()->addDay()->toDateString();

        $todayAppointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => $today,
            'time_slot' => '09:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        $futureAppointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => $tomorrow,
            'time_slot' => '09:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        $todayQueue = Queue::create([
            'appointment_id' => $todayAppointment->id,
            'queue_number' => 'A030',
            'queue_date' => $today,
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $futureQueue = Queue::create([
            'appointment_id' => $futureAppointment->id,
            'queue_number' => 'A031',
            'queue_date' => $tomorrow,
            'status' => 'waiting',
            'is_vip' => true,
        ]);

        $called = app(CallNextQueueEntry::class)->execute();

        $this->assertSame($todayQueue->id, $called?->id);
        $this->assertSame('serving', $todayQueue->fresh()->status);
        $this->assertSame('waiting', $futureQueue->fresh()->status);
    }

    #[Test]
    public function queue_state_transitions_keep_the_appointment_consistent(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => today()->toDateString(),
            'time_slot' => '10:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'A002',
            'queue_date' => today()->toDateString(),
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $queue->update(['status' => 'serving']);
        $this->assertSame(Appointment::STATUS_CONFIRMED, $appointment->fresh()->status);
        $queue->update(['status' => 'completed']);
        $this->assertSame(Appointment::STATUS_COMPLETED, $appointment->fresh()->status);
    }

    #[Test]
    public function queue_status_order_prioritizes_vip_before_regular_customers(): void
    {
        $date = today()->toDateString();

        $regularAppointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => $date,
            'time_slot' => '11:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        $vipAppointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => $date,
            'time_slot' => '11:30',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        Queue::create([
            'appointment_id' => $regularAppointment->id,
            'queue_number' => 'A010',
            'queue_date' => $date,
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $vipQueue = Queue::create([
            'appointment_id' => $vipAppointment->id,
            'queue_number' => 'A011',
            'queue_date' => $date,
            'status' => 'waiting',
            'is_vip' => true,
        ]);

        $queues = app(QueueReader::class)->forDate($date, 'waiting');
        $this->assertSame($vipQueue->id, $queues->first()?->id);
    }

    #[Test]
    public function call_next_selects_the_highest_priority_waiting_customer(): void
    {
        $date = today()->toDateString();

        $regularAppointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => $date,
            'time_slot' => '12:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        $vipAppointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => $date,
            'time_slot' => '12:30',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        Queue::create([
            'appointment_id' => $regularAppointment->id,
            'queue_number' => 'A020',
            'queue_date' => $date,
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $vipQueue = Queue::create([
            'appointment_id' => $vipAppointment->id,
            'queue_number' => 'A021',
            'queue_date' => $date,
            'status' => 'waiting',
            'is_vip' => true,
        ]);

        $called = app(CallNextQueueEntry::class)->execute();
        $this->assertSame($vipQueue->id, $called?->id);
        $this->assertSame('serving', $called?->status);
    }

    #[Test]
    public function completed_queue_entry_cannot_return_to_waiting(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => today()->toDateString(),
            'time_slot' => '13:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'A022',
            'queue_date' => today()->toDateString(),
            'status' => 'completed',
            'is_vip' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(TransitionQueueEntry::class)->execute($queue, 'waiting');
    }

    #[Test]
    public function stale_queue_model_cannot_overwrite_a_newer_terminal_transition(): void
    {
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
            'queue_number' => 'A023',
            'queue_date' => today()->toDateString(),
            'status' => 'serving',
            'is_vip' => false,
        ]);

        $staleQueue = $queue->newQuery()->findOrFail($queue->id);
        $queue->update(['status' => 'completed']);

        $this->expectException(InvalidArgumentException::class);
        app(TransitionQueueEntry::class)->execute($staleQueue, 'waiting');

        $this->assertSame('completed', $queue->fresh()->status);
        $this->assertSame(Appointment::STATUS_COMPLETED, $appointment->fresh()->status);
    }
}
