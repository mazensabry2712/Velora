<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;
use App\Models\Appointment;
use App\Models\Queue;

/**
 * Integration tests: Appointment <-> Queue synchronisation.
 *
 * Verifies the two-way status sync implemented in the booted() observers
 * of Appointment and Queue models.
 *
 * Uses TenantTestCase (tenant-scoped, transactions per test).
 */
class AppointmentQueueIntegrationTest extends TenantTestCase
{
    // -- Helpers ----------------------------------------------------------

    private function makeAppointment(array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'customer_id' => $this->customer->id,
            'staff_id'    => $this->staffMember->id,
            'service_id'  => $this->service->id,
            'date'        => now()->format('Y-m-d'),
            'time_slot'   => '10:00',
            'status'      => 'pending',
        ], $overrides));
    }

    private function makeQueue(Appointment $appointment, array $overrides = []): Queue
    {
        return Queue::create(array_merge([
            'appointment_id' => $appointment->id,
            'queue_number'   => 1,
            'queue_date'     => today()->format('Y-m-d'),
            'status'         => 'waiting',
            'is_vip'         => false,
        ], $overrides));
    }

    // -- Tests -------------------------------------------------------------

    #[Test]
    public function creating_appointment_can_add_to_queue(): void
    {
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $queue = $this->makeQueue($appointment);

        $this->assertNotNull($queue->id);
        $this->assertEquals($appointment->id, $queue->appointment_id);
        $this->assertEquals('waiting', $queue->status);
    }

    #[Test]
    public function cancelling_appointment_sets_queue_to_skipped(): void
    {
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $queue = $this->makeQueue($appointment, ['status' => 'waiting']);

        $appointment->update(['status' => 'cancelled']);

        $this->assertEquals('skipped', $queue->fresh()->status);
    }

    #[Test]
    public function completing_appointment_sets_queue_to_completed(): void
    {
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $queue = $this->makeQueue($appointment, ['status' => 'serving']);

        $appointment->update(['status' => 'completed']);

        $this->assertEquals('completed', $queue->fresh()->status);
    }

    #[Test]
    public function completing_queue_sets_appointment_to_completed(): void
    {
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $queue = $this->makeQueue($appointment, ['status' => 'serving']);

        $queue->update(['status' => 'completed']);

        $this->assertEquals('completed', $appointment->fresh()->status);
    }

    #[Test]
    public function cancelling_queue_sets_appointment_to_cancelled(): void
    {
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $queue = $this->makeQueue($appointment, ['status' => 'waiting']);

        $queue->update(['status' => 'skipped']);

        $this->assertEquals('cancelled', $appointment->fresh()->status);
    }

    #[Test]
    public function queue_serving_status_confirms_appointment(): void
    {
        $appointment = $this->makeAppointment(['status' => 'pending']);
        $queue = $this->makeQueue($appointment, ['status' => 'waiting']);

        $queue->update(['status' => 'serving']);

        $this->assertEquals('confirmed', $appointment->fresh()->status);
    }

    #[Test]
    public function vip_queue_is_ordered_before_regular(): void
    {
        $regularApp = $this->makeAppointment(['time_slot' => '09:00']);
        $vipApp = $this->makeAppointment(['time_slot' => '09:30']);

        $regular = $this->makeQueue($regularApp, ['queue_number' => 2, 'is_vip' => false]);
        $vip = $this->makeQueue($vipApp, ['queue_number' => 3, 'is_vip' => true]);

        // VIP queue items should sort BEFORE regular ones
        $sorted = Queue::whereIn('status', ['waiting', 'serving'])
            ->orderBy('is_vip', 'desc')
            ->orderBy('queue_number', 'asc')
            ->get();

        $this->assertEquals($vip->id, $sorted->first()->id);
        $this->assertEquals($regular->id, $sorted->last()->id);
    }

    #[Test]
    public function deleting_appointment_cascades_to_queue(): void
    {
        $appointment = $this->makeAppointment();
        $queue = $this->makeQueue($appointment);

        $appointmentId = $appointment->id;
        $queueId = $queue->id;

        $appointment->delete();

        // Appointment uses SoftDeletes, check it is soft-deleted
        $this->assertSoftDeleted('appointments', ['id' => $appointmentId]);
        // Queue should be hard-deleted via the deleting observer
        $this->assertDatabaseMissing('queues', ['id' => $queueId]);
    }

    #[Test]
    public function multiple_status_transitions_are_logged(): void
    {
        $appointment = $this->makeAppointment(['status' => 'pending']);

        $appointment->update(['status' => 'confirmed']);
        $appointment->update(['status' => 'completed']);

        $historyCount = \App\Models\AppointmentStatusHistory::where('appointment_id', $appointment->id)->count();
        $this->assertGreaterThanOrEqual(2, $historyCount);
    }
}
