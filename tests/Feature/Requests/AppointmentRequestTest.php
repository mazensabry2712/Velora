<?php

namespace Tests\Feature\Requests;

use Tests\TenantTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;


#[Group('feature')]
#[Group('requests')]
#[Group('appointments')]
class AppointmentRequestTest extends TenantTestCase
{
    // ════════════════════════════════════════════════════════════════════════
    // StoreAppointmentRequest
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function store_passes_with_all_required_fields(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.appointments.store'), [
            'customer_name'    => 'Valid Customer',
            'customer_phone'   => '0501234567',
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '10:00',
        ]);

        // Should not be a validation error (may be 200 or 201, not 422)
        $this->assertNotEquals(422, $response->status());
    }

    #[Test]
    public function store_requires_customer_name(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.appointments.store'), [
            'customer_phone'   => '0501234567',
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '10:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_name']);
    }

    #[Test]
    public function store_requires_customer_phone(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.appointments.store'), [
            'customer_name'    => 'Customer',
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '10:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_phone']);
    }

    #[Test]
    public function store_requires_appointment_date(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.appointments.store'), [
            'customer_name'    => 'Customer',
            'customer_phone'   => '0501234567',
            'appointment_time' => '10:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['appointment_date']);
    }

    #[Test]
    public function store_requires_appointment_time(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.appointments.store'), [
            'customer_name'    => 'Customer',
            'customer_phone'   => '0501234567',
            'appointment_date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['appointment_time']);
    }

    #[Test]
    public function store_rejects_appointment_date_in_the_past(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.appointments.store'), [
            'customer_name'    => 'Customer',
            'customer_phone'   => '0501234567',
            'appointment_date' => now()->subDays(3)->format('Y-m-d'),
            'appointment_time' => '10:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['appointment_date']);
    }

    #[Test]
    public function store_accepts_today_as_appointment_date(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.appointments.store'), [
            'customer_name'    => 'Customer',
            'customer_phone'   => '0501234567',
            'appointment_date' => now()->format('Y-m-d'),
            'appointment_time' => '10:00',
        ]);

        // Should not fail with appointment_date validation error
        $this->assertNotContains(
            'appointment_date',
            array_keys($response->json('errors') ?? [])
        );
    }

    #[Test]
    public function store_rejects_invalid_email_format(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.appointments.store'), [
            'customer_name'    => 'Customer',
            'customer_phone'   => '050',
            'customer_email'   => 'not-an-email',
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '10:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_email']);
    }

    #[Test]
    public function store_rejects_non_existent_staff_id(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.appointments.store'), [
            'customer_name'    => 'Customer',
            'customer_phone'   => '050',
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '10:00',
            'staff_id'         => 99999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['staff_id']);
    }

    // ════════════════════════════════════════════════════════════════════════
    // UpdateAppointmentRequest
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function update_accepts_valid_status_values(): void
    {
        $this->actingAs($this->admin);
        $appt = \App\Models\Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id'    => $this->staffMember->id,
            'service_id'  => $this->service->id,
            'date'        => today()->addDay(),
            'time_slot'   => '10:00',
            'status'      => 'pending',
        ]);

        foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $status) {
            $response = $this->putJson(route('admin.api.appointments.update', $appt->id), [
                'customer_name'    => 'Customer',
                'customer_phone'   => '0501234567',
                'appointment_date' => now()->addDay()->format('Y-m-d'),
                'appointment_time' => '11:00',
                'status'           => $status,
            ]);
            $this->assertNotContains(
                'status',
                array_keys($response->json('errors') ?? []),
                "Status '{$status}' should be valid"
            );
        }
    }

    #[Test]
    public function update_rejects_invalid_status(): void
    {
        $this->actingAs($this->admin);
        $appt = \App\Models\Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id'    => $this->staffMember->id,
            'service_id'  => $this->service->id,
            'date'        => today()->addDay(),
            'time_slot'   => '10:00',
            'status'      => 'pending',
        ]);

        $response = $this->putJson(route('admin.api.appointments.update', $appt->id), [
            'customer_name'    => 'Customer',
            'customer_phone'   => '0501234567',
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '11:00',
            'status'           => 'teleporting',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }
}
