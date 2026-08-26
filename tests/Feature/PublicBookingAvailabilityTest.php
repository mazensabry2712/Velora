<?php

namespace Tests\Feature;

use App\Models\StaffWorkingHours;
use Tests\TenantTestCase;
use PHPUnit\Framework\Attributes\Test;

class PublicBookingAvailabilityTest extends TenantTestCase
{
    #[Test]
    public function public_availability_matches_service_duration_and_staff_hours(): void
    {
        $this->service->update([
            'is_online_bookable' => true,
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
        ]);

        $date = now(config('app.timezone'))->addDay()->startOfDay();

        StaffWorkingHours::create([
            'staff_id' => $this->staff->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'is_working' => true,
        ]);

        $response = $this->getJson('/api/booking/available-timeslots?' . http_build_query([
            'date' => $date->toDateString(),
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => config('app.timezone'),
        ]));

        $response->assertOk()->assertJson(['success' => true]);

        $times = collect($response->json('data'))->pluck('start_time')->all();

        $this->assertSame(['09:00', '09:15', '09:30'], $times);
    }

    #[Test]
    public function public_availability_rejects_non_bookable_selection_without_exposing_internal_error(): void
    {
        $this->service->update(['is_online_bookable' => false]);

        $date = now(config('app.timezone'))->addDay()->toDateString();

        $response = $this->getJson('/api/booking/available-timeslots?' . http_build_query([
            'date' => $date,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => config('app.timezone'),
        ]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [],
                'reason' => 'invalid_booking_selection',
            ]);
    }
}
