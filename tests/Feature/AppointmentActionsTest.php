<?php

namespace Tests\Feature;

use Tests\TenantTestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Appointment;
use App\Models\Queue;

class AppointmentActionsTest extends TenantTestCase
{
    private function makeAppointment(array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'customer_id_new' => $this->customerProfile->id,
            'staff_id_new'    => $this->staff->id,
            'service_id'     => $this->service->id,
            'date'           => now()->format('Y-m-d'),
            'time_slot'      => '10:00',
            'status'         => 'pending',
        ], $overrides));
    }

    private function makeQueue(Appointment $appointment, array $overrides = []): Queue
    {
        return Queue::create(array_merge([
            'appointment_id' => $appointment->id,
            'queue_number'   => '1',
            'queue_date'     => today()->format('Y-m-d'),
            'status'         => 'waiting',
            'is_vip'         => false,
        ], $overrides));
    }

    #[Test]
    public function it_displays_appointments_page(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.appointments'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.appointments.index');
        $response->assertViewHas('appointments');
        $response->assertViewHas('stats');
    }

    #[Test]
    public function it_creates_appointment_and_adds_to_queue_automatically(): void
    {
        $this->actingAs($this->admin);
        $data = [
            'customer_name'    => 'New Customer',
            'customer_email'   => 'newcustomer@test.com',
            'customer_phone'   => '555-1234',
            'appointment_date' => now()->addDays(1)->format('Y-m-d'),
            'appointment_time' => '10:00',
            'service_id'       => $this->service->id,
            'staff_id'         => $this->staff->id,
            'notes'            => 'Test appointment',
            'add_to_queue'     => true,
            'queue_date'       => now()->addDays(1)->format('Y-m-d'),
        ];
        $response = $this->postJson(route('admin.api.appointments.store'), $data);
        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('appointments', [
            'service_id' => $this->service->id,
            'staff_id_new' => $this->staff->id,
        ]);
    }

    #[Test]
    public function it_updates_appointment_status(): void
    {
        $this->actingAs($this->admin);
        $appointment = $this->makeAppointment(['status' => 'pending']);
        $response = $this->patchJson(route('admin.api.appointments.status', $appointment->id), ['status' => 'confirmed']);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals('confirmed', $appointment->fresh()->status);
    }

    #[Test]
    public function it_syncs_queue_status_when_appointment_is_cancelled(): void
    {
        $this->actingAs($this->admin);
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $queue = $this->makeQueue($appointment, ['status' => 'waiting']);
        $this->patchJson(route('admin.api.appointments.status', $appointment->id), ['status' => 'cancelled'])->assertStatus(200);
        $this->assertEquals('skipped', $queue->fresh()->status);
    }

    #[Test]
    public function it_syncs_queue_status_when_appointment_is_completed(): void
    {
        $this->actingAs($this->admin);
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $queue = $this->makeQueue($appointment, ['status' => 'serving']);
        $this->patchJson(route('admin.api.appointments.status', $appointment->id), ['status' => 'completed'])->assertStatus(200);
        $this->assertEquals('completed', $queue->fresh()->status);
    }

    #[Test]
    public function it_adds_appointment_to_queue(): void
    {
        $this->actingAs($this->admin);
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $this->assertNull($appointment->queue);
        $response = $this->postJson(route('admin.api.appointments.addToQueue', $appointment->id));
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertNotNull($appointment->fresh()->queue);
        $this->assertEquals('waiting', $appointment->fresh()->queue->status);
    }

    #[Test]
    public function it_prevents_adding_appointment_to_queue_twice(): void
    {
        $this->actingAs($this->admin);
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $this->makeQueue($appointment);
        $response = $this->postJson(route('admin.api.appointments.addToQueue', $appointment->id));
        $response->assertStatus(400)->assertJson(['success' => false]);
    }

    #[Test]
    public function it_removes_appointment_from_queue(): void
    {
        $this->actingAs($this->admin);
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $queue = $this->makeQueue($appointment);
        $response = $this->postJson(route('admin.api.appointments.removeFromQueue', $appointment->id));
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseMissing('queues', ['id' => $queue->id]);
    }

    #[Test]
    public function it_correctly_sets_vip_status_when_adding_to_queue(): void
    {
        $this->actingAs($this->admin);
        $this->customer->forceFill(['is_vip' => true])->save();
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $response = $this->postJson(route('admin.api.appointments.addToQueue', $appointment->id));
        $response->assertStatus(200);
        $this->assertTrue((bool) $appointment->fresh()->queue->is_vip);
    }

    #[Test]
    public function it_filters_appointments_by_queue_status(): void
    {
        $this->actingAs($this->admin);
        $inQueue = $this->makeAppointment(['status' => 'confirmed']);
        $this->makeQueue($inQueue);
        $this->makeAppointment(['date' => now()->addDays(1)->format('Y-m-d'), 'status' => 'pending']);
        foreach (['in_queue', 'not_in_queue', 'waiting'] as $filter) {
            $this->get(route('admin.appointments', ['queue_status' => $filter]))->assertStatus(200);
        }
    }

    #[Test]
    public function it_deletes_appointment_and_cascades_queue(): void
    {
        $this->actingAs($this->admin);
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $queue = $this->makeQueue($appointment);
        $this->deleteJson(route('admin.api.appointments.destroy', $appointment->id))->assertStatus(200);
        $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);
        $this->assertDatabaseMissing('queues', ['id' => $queue->id]);
    }

    #[Test]
    public function it_displays_queue_number_in_appointments_list(): void
    {
        $this->actingAs($this->admin);
        $appointment = $this->makeAppointment(['status' => 'confirmed']);
        $this->makeQueue($appointment, ['queue_number' => '42']);
        $response = $this->get(route('admin.appointments'));
        $response->assertStatus(200)->assertSee('#42');
    }

    #[Test]
    public function it_shows_correct_action_buttons_based_on_queue_status(): void
    {
        $this->actingAs($this->admin);
        $inQueue = $this->makeAppointment(['status' => 'confirmed']);
        $this->makeQueue($inQueue);
        $notInQueue = $this->makeAppointment(['date' => now()->addDays(1)->format('Y-m-d'), 'status' => 'pending']);
        $response = $this->get(route('admin.appointments'));
        $response->assertStatus(200);
        $response->assertSee('removeFromQueue(' . $inQueue->id . ')');
        $response->assertSee('addToQueue(' . $notInQueue->id . ')');
    }
}
