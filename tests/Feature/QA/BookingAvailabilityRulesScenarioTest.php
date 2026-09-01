<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Application\Booking\Actions\CreatePublicBooking;
use App\Application\Booking\DTOs\PublicBookingData;
use App\Domain\Booking\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\BusinessRule;
use App\Models\Holiday;
use App\Models\StaffWorkingHours;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class BookingAvailabilityRulesScenarioTest extends TenantTestCase
{
    private function prepareDate(Carbon $date): string
    {
        $timezone = $this->staff->timezone ?: config('app.timezone');
        $localDate = $date->copy()->setTimezone($timezone)->startOfDay();

        $this->service->forceFill([
            'is_active' => true,
            'is_online_bookable' => true,
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
        ])->save();

        StaffWorkingHours::create([
            'staff_id' => $this->staff->id,
            'day_of_week' => $localDate->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_working' => true,
        ]);

        return $timezone;
    }

    private function data(Carbon $date, string $time, string $email = 'availability-rules@example.com'): PublicBookingData
    {
        return new PublicBookingData(
            customerName: 'Availability Rules Customer',
            customerEmail: $email,
            customerPhone: '+201000000030',
            serviceId: $this->service->id,
            staffUserId: $this->staffMember->id,
            resourceId: null,
            appointmentDate: $date->toDateString(),
            appointmentTime: $time,
            requestedTimezone: $this->staff->timezone ?: config('app.timezone'),
            notes: null,
        );
    }

    private function assertSlotUnavailableWithReason(callable $operation, string $reason): void
    {
        try {
            $operation();
            $this->fail("Expected SlotUnavailableException with reason [{$reason}].");
        } catch (SlotUnavailableException $exception) {
            $this->assertSame($reason, $exception->getReason());
        }
    }

    #[Test]
    public function holiday_makes_the_staff_unavailable_even_when_working_hours_exist(): void
    {
        $date = now($this->staff->timezone ?: config('app.timezone'))->addDays(2)->startOfDay();
        $this->prepareDate($date);

        Holiday::create([
            'date' => $date->toDateString(),
            'name' => ['en' => 'QA Holiday'],
            'applies_to_all' => true,
        ]);

        $this->assertSlotUnavailableWithReason(
            fn () => app(CreatePublicBooking::class)->execute($this->data($date, '09:00')),
            'holiday',
        );
    }

    #[Test]
    public function same_day_booking_can_be_disabled_by_business_rule(): void
    {
        $timezone = $this->staff->timezone ?: config('app.timezone');
        $date = now($timezone)->startOfDay();
        $this->prepareDate($date);

        BusinessRule::setValue(BusinessRule::ALLOW_SAME_DAY_BOOKING, false, 'boolean');

        $bookingTime = now($timezone)->addHours(2)->ceilHour()->format('H:i');

        $this->assertSlotUnavailableWithReason(
            fn () => app(CreatePublicBooking::class)->execute($this->data($date, $bookingTime, 'same-day@example.com')),
            'same_day_booking_not_allowed',
        );
    }

    #[Test]
    public function maximum_advance_booking_days_are_enforced(): void
    {
        $timezone = $this->staff->timezone ?: config('app.timezone');
        $date = now($timezone)->addDays(3)->startOfDay();
        $this->prepareDate($date);

        BusinessRule::setValue(BusinessRule::MAX_ADVANCE_BOOKING_DAYS, 1, 'integer');

        $this->assertSlotUnavailableWithReason(
            fn () => app(CreatePublicBooking::class)->execute($this->data($date, '09:00', 'advance-limit@example.com')),
            'too_far_in_advance',
        );
    }

    #[Test]
    public function customer_daily_booking_limit_is_enforced_without_creating_a_second_appointment(): void
    {
        $timezone = $this->staff->timezone ?: config('app.timezone');
        $date = now($timezone)->addDay()->startOfDay();
        $this->prepareDate($date);

        BusinessRule::setValue(BusinessRule::MAX_BOOKINGS_PER_CUSTOMER_PER_DAY, 1, 'integer');

        $action = app(CreatePublicBooking::class);
        $first = $action->execute($this->data($date, '09:00', 'daily-limit@example.com'));

        $this->assertSlotUnavailableWithReason(
            fn () => $action->execute($this->data($date, '10:00', 'daily-limit@example.com')),
            'max_bookings_per_day_reached',
        );

        $this->assertSame(
            1,
            Appointment::query()
                ->where('customer_id_new', $first['customer']->id)
                ->whereDate('starts_at', $date->toDateString())
                ->count(),
        );
    }
}
