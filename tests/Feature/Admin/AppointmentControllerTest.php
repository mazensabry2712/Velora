<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Queue;
use Tests\TenantTestCase;


#[Group('feature')]
#[Group('admin')]
#[Group('appointments')]
class AppointmentControllerTest extends TenantTestCase
{
    // ── Authentication guard ──────────────────────────────────────────────

    #[Test]
    public function guests_cannot_access_appointments_page(): void
    {
        $response = $this->get(route('admin.appointments'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_appointments_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.appointments'));

        $response->assertOk();
        $response->assertViewIs('admin.appointments.index');
    }

    // ── store ─────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_create_appointment(): void
    {
        $this->actingAs($this->admin);

        $payload = [
            'customer_name'    => 'Khalid Ahmed',
            'customer_phone'   => '0509876543',
            'customer_email'   => 'khalid@example.com',
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '09:00',
            'service_id'       => $this->service->id,
            'staff_id'         => $this->staffMember->id,
        ];

        $response = $this->postJson(route('admin.api.appointments.store'), $payload);

        $response->assertCreated();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('appointments', [
            'service_id' => $this->service->id,
            'staff_id'   => $this->staffMember->id,
        ]);
    }

    #[Test]
    public function store_requires_customer_name_and_phone(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.appointments.store'), [
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '09:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_name', 'customer_phone']);
    }

    #[Test]
    public function store_rejects_past_appointment_date(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.appointments.store'), [
            'customer_name'    => 'Test',
            'customer_phone'   => '050',
            'appointment_date' => now()->subDay()->format('Y-m-d'),
            'appointment_time' => '09:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['appointment_date']);
    }

    // ── show ──────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_fetch_single_appointment_as_json(): void
    {
        $this->actingAs($this->admin);
        $appt = $this->makeAppointment();

        $response = $this->getJson(route('admin.api.appointments.show', $appt->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('data.id', $appt->id);
    }

    #[Test]
    public function show_returns_404_for_missing_appointment(): void
    {
        $this->actingAs($this->admin);

        $response = $this->getJson(route('admin.api.appointments.show', 99999));

        $response->assertNotFound();
    }

    // ── update ────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_update_appointment(): void
    {
        $this->actingAs($this->admin);
        $appt = $this->makeAppointment(['status' => 'pending']);

        $response = $this->putJson(route('admin.api.appointments.update', $appt->id), [
            'customer_name'    => 'Updated Name',
            'customer_phone'   => '0501111111',
            'appointment_date' => now()->addDays(2)->format('Y-m-d'),
            'appointment_time' => '11:00',
            'status'           => 'confirmed',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    // ── quickStatus ───────────────────────────────────────────────────────

    #[Test]
    public function quick_status_updates_appointment_status(): void
    {
        $this->actingAs($this->admin);
        $appt = $this->makeAppointment(['status' => 'pending']);

        $response = $this->patchJson(route('admin.api.appointments.status', $appt->id), [
            'status' => 'confirmed',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertEquals('confirmed', $appt->fresh()->status);
    }

    #[Test]
    public function quick_status_rejects_invalid_status_value(): void
    {
        $this->actingAs($this->admin);
        $appt = $this->makeAppointment();

        $response = $this->patchJson(route('admin.api.appointments.status', $appt->id), [
            'status' => 'flying_saucer',
        ]);

        $response->assertStatus(422);
    }

    // ── destroy ───────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_delete_appointment(): void
    {
        $this->actingAs($this->admin);
        $appt = $this->makeAppointment();

        $response = $this->deleteJson(route('admin.api.appointments.destroy', $appt->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertSoftDeleted('appointments', ['id' => $appt->id]);
    }

    // ── addToQueue / removeFromQueue ──────────────────────────────────────

    #[Test]
    public function admin_can_add_appointment_to_queue(): void
    {
        $this->actingAs($this->admin);
        $appt = $this->makeAppointment(['status' => 'confirmed']);

        $response = $this->postJson(route('admin.api.appointments.addToQueue', $appt->id), [
            'queue_date' => today()->format('Y-m-d'),
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('queues', ['appointment_id' => $appt->id]);
    }

    #[Test]
    public function admin_can_remove_appointment_from_queue(): void
    {
        $this->actingAs($this->admin);
        $appt  = $this->makeAppointment();
        Queue::create([
            'appointment_id' => $appt->id,
            'queue_number'   => '1',
            'status'         => 'waiting',
        ]);

        $response = $this->postJson(route('admin.api.appointments.removeFromQueue', $appt->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    // ── Bulk Action ───────────────────────────────────────────────────────

    #[Test]
    public function bulk_day_action_can_confirm_all_pending_on_a_date(): void
    {
        $this->actingAs($this->admin);
        $date = today()->addDays(3)->format('Y-m-d');
        $a1   = $this->makeAppointment(['date' => $date, 'status' => 'pending']);
        $a2   = $this->makeAppointment(['date' => $date, 'status' => 'pending']);

        $response = $this->postJson(route('admin.api.appointments.bulkDayAction'), [
            'date'            => $date,
            'action'          => 'confirm_all',
            'appointment_ids' => [$a1->id, $a2->id],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    // ── Rate ─────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_rate_a_completed_appointment(): void
    {
        $this->actingAs($this->admin);
        $appt = $this->makeAppointment(['status' => 'completed']);

        $response = $this->postJson(route('admin.api.appointments.rate', $appt->id), [
            'rating'         => 5,
            'rating_comment' => 'Excellent service.',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function makeAppointment(array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'customer_id' => $this->customer->id,
            'staff_id'    => $this->staffMember->id,
            'service_id'  => $this->service->id,
            'date'        => today()->addDay(),
            'time_slot'   => '10:00',
            'status'      => 'pending',
        ], $overrides));
    }
}
