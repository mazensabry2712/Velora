<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Queue;
use App\Models\StaffWorkingHours;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

class CustomerBookingJourneyTest extends TenantTestCase
{
    private function prepareBookableSlot(): array
    {
        $timezone = $this->staff->timezone ?: config('app.timezone');

        $this->service->update([
            'is_active' => true,
            'is_online_bookable' => true,
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
        ]);

        $date = now($timezone)->addDay()->startOfDay();

        StaffWorkingHours::create([
            'staff_id' => $this->staff->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'is_working' => true,
        ]);

        RateLimiter::clear('public-booking:' . $this->tenant->getTenantKey() . ':' . $this->app->make('request')->ip());

        return [$date, $timezone];
    }

    private function book(array $payload): array
    {
        $response = $this->postJson('/api/appointments', $payload);
        $response->assertCreated();

        $appointment = Appointment::query()->latest('id')->firstOrFail();
        $queue = Queue::query()->where('appointment_id', $appointment->id)->firstOrFail();

        return [$response, $appointment, $queue];
    }

    #[Test]
    public function customer_can_complete_booking_and_booked_slot_disappears(): void
    {
        [$date, $timezone] = $this->prepareBookableSlot();

        $payload = [
            'customer_name' => 'Journey Customer',
            'customer_email' => 'journey@example.com',
            'customer_phone' => '+201000000000',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => $timezone,
            'notes' => 'Please arrive 10 minutes early.',
        ];

        $response = $this->postJson('/api/appointments', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Appointment booked successfully');

        $appointment = Appointment::query()->latest('id')->firstOrFail();
        $queue = Queue::query()->where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertSame($this->service->id, $appointment->service_id);
        $this->assertSame($this->staff->id, $appointment->staff_id_new);
        $this->assertNotNull($appointment->customer_id_new);
        $this->assertNull($appointment->customer_id);
        $this->assertSame('pending', $appointment->status);
        $this->assertSame('waiting', $queue->status);
        $this->assertNotSame('', (string) $queue->queue_number);
        $this->assertSame($date->toDateString(), $queue->queue_date->toDateString());

        $availability = $this->getJson('/api/booking/available-timeslots?' . http_build_query([
            'date' => $date->toDateString(),
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => $timezone,
        ]));

        $availability->assertOk()->assertJsonPath('success', true);

        $times = collect($availability->json('data'))->pluck('start_time')->all();
        $this->assertNotContains('09:00', $times);
    }

    #[Test]
    public function completed_new_booking_generates_invoice_for_the_new_customer(): void
    {
        [, $appointment] = $this->book($this->bookingPayloadForJourney());

        $this->assertSame(0, Invoice::query()->where('appointment_id', $appointment->id)->count());

        $appointment->update(['status' => Appointment::STATUS_COMPLETED]);

        $invoice = Invoice::query()->where('appointment_id', $appointment->id)->first();

        $this->assertNotNull($invoice);
        $this->assertSame($appointment->customer_id_new, $invoice->customer_id);
        $this->assertSame('100.00', (string) $invoice->amount);
        $this->assertSame('pending', $invoice->status);
    }

    private function bookingPayloadForJourney(): array
    {
        [$date, $timezone] = $this->prepareBookableSlot();

        return [
            'customer_name' => 'Invoice Journey Customer',
            'customer_email' => 'invoice-journey@example.com',
            'customer_phone' => '+201000000003',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => $timezone,
        ];
    }

    #[Test]
    public function customer_can_check_the_queue_status_after_booking(): void
    {
        [$date, $timezone] = $this->prepareBookableSlot();

        [, $appointment, $queue] = $this->book([
            'customer_name' => 'Queue Customer',
            'customer_email' => 'queue@example.com',
            'customer_phone' => '+201000000002',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => $timezone,
        ]);

        $this->getJson('/api/queue/status/' . rawurlencode((string) $queue->queue_number))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.queue_number', $queue->queue_number)
            ->assertJsonPath('data.status', 'waiting')
            ->assertJsonMissingPath('data.customer_name')
            ->assertJsonMissingPath('data.notes')
            ->assertJsonPath('data.service', $this->service->name)
            ->assertJsonPath('data.staff_name', $this->staffMember->name);

        $this->assertSame($queue->id, Queue::query()->where('appointment_id', $appointment->id)->value('id'));
    }

    #[Test]
    public function customer_cannot_book_the_same_slot_twice(): void
    {
        [$date, $timezone] = $this->prepareBookableSlot();

        $payload = [
            'customer_name' => 'Duplicate Customer',
            'customer_email' => 'duplicate@example.com',
            'customer_phone' => '+201000000001',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => $timezone,
        ];

        $this->postJson('/api/appointments', $payload)->assertCreated();

        RateLimiter::clear('public-booking:' . $this->tenant->getTenantKey() . ':' . $this->app->make('request')->ip());

        $response = $this->postJson('/api/appointments', $payload);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Slot not available');

        $this->assertSame(
            1,
            Appointment::query()
                ->where('staff_id_new', $this->staff->id)
                ->where('service_id', $this->service->id)
                ->whereDate('starts_at', $date->toDateString())
                ->count()
        );
    }

    #[Test]
    public function blocked_customer_cannot_create_a_public_booking(): void
    {
        [$date, $timezone] = $this->prepareBookableSlot();

        $blockedEmail = 'blocked@example.com';

        Customer::create([
            'first_name' => 'Blocked',
            'last_name' => 'Customer',
            'email' => $blockedEmail,
            'phone' => '+201000000099',
            'is_blocked' => true,
            'block_reason' => 'Repeated no-shows',
            'acquisition_source' => 'online',
        ]);

        $response = $this->postJson('/api/appointments', [
            'customer_name' => 'Blocked Customer',
            'customer_email' => $blockedEmail,
            'customer_phone' => '+201000000099',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => $timezone,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error')
            ->assertJsonPath('errors.customer_email.0', 'This customer is not allowed to book appointments.');

        $this->assertSame(0, Appointment::query()->where('service_id', $this->service->id)->count());
        $this->assertSame(1, Customer::query()->where('email', $blockedEmail)->count());
    }
}