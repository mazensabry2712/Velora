<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Queue;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

class CustomerPortalJourneyTest extends TenantTestCase
{
    private function customerRecordForUser(): Customer
    {
        return Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => $this->customer->email,
            'phone' => $this->customer->phone ?? '+201000000000',
            'acquisition_source' => 'online',
            'is_blocked' => false,
        ]);
    }

    #[Test]
    public function authenticated_customer_can_see_their_new_booking_history(): void
    {
        $customer = $this->customerRecordForUser();

        $appointment = Appointment::create([
            'customer_id_new' => $customer->id,
            'staff_id_new' => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(9, 30),
            'ends_at_with_buffer' => now()->addDay()->setTime(9, 30),
            'timezone' => config('app.timezone'),
            'price' => 100,
            'attendees' => 1,
            'source' => 'online',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $other = Customer::create([
            'first_name' => 'Other',
            'last_name' => 'Customer',
            'email' => 'other@example.com',
            'phone' => '+201000000099',
            'acquisition_source' => 'online',
            'is_blocked' => false,
        ]);

        Appointment::create([
            'customer_id_new' => $other->id,
            'staff_id_new' => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(10, 30),
            'ends_at_with_buffer' => now()->addDay()->setTime(10, 30),
            'timezone' => config('app.timezone'),
            'price' => 100,
            'attendees' => 1,
            'source' => 'online',
            'status' => Appointment::STATUS_PENDING,
        ]);

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

        $appointment = Appointment::create([
            'customer_id_new' => $customer->id,
            'staff_id_new' => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at' => now()->setTime(9, 0),
            'ends_at' => now()->setTime(9, 30),
            'ends_at_with_buffer' => now()->setTime(9, 30),
            'timezone' => config('app.timezone'),
            'price' => 100,
            'attendees' => 1,
            'source' => 'online',
            'status' => Appointment::STATUS_PENDING,
        ]);

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

        $appointment = Appointment::create([
            'customer_id_new' => $customer->id,
            'staff_id_new' => $this->staff->id,
            'service_id' => $this->service->id,
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(9, 30),
            'ends_at_with_buffer' => now()->addDay()->setTime(9, 30),
            'timezone' => config('app.timezone'),
            'price' => 100,
            'attendees' => 1,
            'source' => 'online',
            'status' => Appointment::STATUS_COMPLETED,
        ]);

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
