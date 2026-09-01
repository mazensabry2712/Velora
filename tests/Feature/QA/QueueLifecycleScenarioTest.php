<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Domain\Queue\Contracts\QueueReader;
use App\Models\Appointment;
use App\Models\Queue;
use Carbon\Carbon;
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
}
