<?php

namespace Tests\Feature;

use App\Models\Appointment;
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
        $this->assertSame('pending', $appointment->status);
        $this->assertSame('waiting', $queue->status);
        $this->assertNotSame('', (string) $queue->queue_number);
        $this->assertSame($date->toDateString(), $queue->queue_date);

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
                ->whereTime('starts_at', '09:00')
                ->count()
        );
    }
}
