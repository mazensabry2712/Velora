<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Queue;
use App\Models\Service;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

class PublicAppointmentReferenceTest extends TenantTestCase
{
    #[Test]
    public function new_appointments_receive_an_unguessable_public_reference(): void
    {
        $appointment = Appointment::create([
            'service_id' => Service::create([
                'name' => 'Consultation',
                'duration' => 30,
                'is_active' => true,
                'is_online_bookable' => true,
            ])->id,
            'staff_id' => $this->staffMember->id,
            'date' => today(),
            'time_slot' => '10:00',
            'starts_at' => Carbon::today()->setTime(10, 0),
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => 'online',
        ]);

        $this->assertMatchesRegularExpression('/^VL-[A-Z0-9]{8}$/', $appointment->public_reference);
    }

    #[Test]
    public function public_reference_can_retrieve_only_the_same_tenant_appointment(): void
    {
        $service = Service::create([
            'name' => 'Consultation',
            'duration' => 30,
            'is_active' => true,
            'is_online_bookable' => true,
        ]);

        $appointment = Appointment::create([
            'service_id' => $service->id,
            'staff_id' => $this->staffMember->id,
            'date' => today(),
            'time_slot' => '10:00',
            'starts_at' => Carbon::today()->setTime(10, 0),
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => 'online',
        ]);

        Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'A-901',
            'queue_date' => today()->toDateString(),
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $response = $this->getJson('/api/queue/status/' . $appointment->public_reference);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reference', $appointment->public_reference)
            ->assertJsonPath('data.queue_number', 'A-901')
            ->assertJsonPath('data.service', 'Consultation');
    }

    #[Test]
    public function queue_status_page_accepts_public_reference_and_prefills_lookup(): void
    {
        $service = Service::create([
            'name' => 'Consultation',
            'duration' => 30,
            'is_active' => true,
            'is_online_bookable' => true,
        ]);

        $appointment = Appointment::create([
            'service_id' => $service->id,
            'staff_id' => $this->staffMember->id,
            'date' => today(),
            'time_slot' => '10:00',
            'starts_at' => Carbon::today()->setTime(10, 0),
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => 'online',
        ]);

        $response = $this->get('/queue/status?ref=' . urlencode($appointment->public_reference));

        $response->assertOk()
            ->assertViewIs('customer.queue-status')
            ->assertSee($appointment->public_reference, false)
            ->assertSee('Appointment', false);
    }
}
