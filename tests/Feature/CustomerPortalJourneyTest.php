<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

class CustomerPortalJourneyTest extends TenantTestCase
{
    private function customerRecordForUser(): Customer
    {
        return $this->customerProfile;
    }

    private function appointmentAttributes(Customer $customer, string $time, string $status = Appointment::STATUS_PENDING): array
    {
        $date = now()->addDay()->toDateString();
        $startsAt = now()->addDay()->setTimeFromTimeString($time);
        $endsAt = $startsAt->copy()->addMinutes(30);

        return [
            'customer_id_new' => $customer->id,
            'staff_id_new' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => $date,
            'time_slot' => $time,
            'service_type' => $this->service->name ?? 'Consultation',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'ends_at_with_buffer' => $endsAt,
            'timezone' => config('app.timezone'),
            'price' => 100,
            'attendees' => 1,
            'source' => 'online',
            'status' => $status,
        ];
    }

    #[Test]
    public function authenticated_customer_can_see_their_new_booking_history(): void
    {
        $customer = $this->customerRecordForUser();

        $appointment = Appointment::create($this->appointmentAttributes($customer, '09:00'));

        $other = Customer::create([
            'first_name' => 'Other',
            'last_name' => 'Customer',
            'email' => 'other@example.com',
            'phone' => '+201000000099',
            'acquisition_source' => 'online',
            'is_blocked' => false,
        ]);

        Appointment::create($this->appointmentAttributes($other, '10:00'));

        $response = $this->actingAs($this->customer)->getJson('/api/my-appointments');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $appointment->id)
            ->assertJsonPath('data.0.customer_id_new', $customer->id);
    }

    #[Test]
    public function authenticated_customer_can_see_their_new_booking_queue(): void
    {
        $customer = $this->customerRecordForUser();

        $appointment = Appointment::create($this->appointmentAttributes($customer, '09:00'));

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'Q-PORTAL-01',
            'queue_date' => now()->toDateString(),
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $response = $this->actingAs($this->customer)->getJson('/api/my-queue');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.queue.id', $queue->id)
            ->assertJsonPath('data.queue.queue_number', 'Q-PORTAL-01')
            ->assertJsonPath('data.position', 1);
    }

    #[Test]
    public function authenticated_customer_can_see_their_new_booking_invoices(): void
    {
        $customer = $this->customerRecordForUser();

        $appointment = Appointment::create(
            $this->appointmentAttributes($customer, '09:00', Appointment::STATUS_COMPLETED)
        );

        $invoice = Invoice::create([
            'number' => 'INV-PORTAL-001',
            'customer_id' => $customer->id,
            'appointment_id' => $appointment->id,
            'amount' => 100,
            'status' => 'pending',
            'issued_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)->getJson('/api/my-invoices');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $invoice->id)
            ->assertJsonPath('data.0.number', 'INV-PORTAL-001')
            ->assertJsonPath('data.0.customer.id', $customer->id);
    }
}
