<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Application\Booking\Actions\CreatePublicBooking;
use App\Application\Booking\DTOs\PublicBookingData;
use App\Domain\Booking\Exceptions\SlotUnavailableException;
use App\Domain\Booking\Services\SlotEngine;
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
    private function prepareDate(Carbon $date, string $startTime = '09:00', string $endTime = '17:00'): string
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

        StaffWorkingHours::updateOrCreate(
            [
                'staff_id' => $this->staff->id,
                'day_of_week' => $localDate->dayOfWeek,
            ],
            [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_working' => true,
            ],
        );

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
        $timezone = $this->prepareDate($date);

        Holiday::create([
            'date' => $date->toDateString(),
            'name' => ['en' => 'QA Holiday'],
            'applies_to_all' => true,
        ]);

        $this->assertTrue(
            Holiday::query()
                ->whereDate('date', $date->toDateString())
                ->where('applies_to_all', true)
                ->exists(),
            'The QA holiday fixture was not persisted in the current tenant database.'
        );

        $result = app(SlotEngine::class)->validateSlot(
            $this->service,
            $this->staff,
            Carbon::createFromFormat('Y-m-d H:i', $date->toDateString() . ' 09:00', $timezone),
        );

        $this->assertFalse($result->isAvailable(), 'SlotEngine unexpectedly considered a holiday slot available.');
        $this->assertSame('holiday', $result->getReason());

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

    #[Test]
    public function working_hours_boundary_matrix_accepts_start_and_last_valid_slot_but_rejects_outside_bounds(): void
    {
        $timezone = $this->staff->timezone ?: config('app.timezone');
        $date = now($timezone)->addDays(4)->startOfDay();
        $this->prepareDate($date, '09:00', '17:00');

        $engine = app(SlotEngine::class);

        $beforeStart = $engine->validateSlot(
            $this->service,
            $this->staff,
            Carbon::createFromFormat('Y-m-d H:i', $date->toDateString() . ' 08:59', $timezone),
        );
        $this->assertFalse($beforeStart->isAvailable());
        $this->assertSame('outside_working_hours', $beforeStart->getReason());

        $atStart = $engine->validateSlot(
            $this->service,
            $this->staff,
            Carbon::createFromFormat('Y-m-d H:i', $date->toDateString() . ' 09:00', $timezone),
        );
        $this->assertTrue($atStart->isAvailable());

        $lastValid = $engine->validateSlot(
            $this->service,
            $this->staff,
            Carbon::createFromFormat('Y-m-d H:i', $date->toDateString() . ' 16:30', $timezone),
        );
        $this->assertTrue($lastValid->isAvailable());

        $afterEnd = $engine->validateSlot(
            $this->service,
            $this->staff,
            Carbon::createFromFormat('Y-m-d H:i', $date->toDateString() . ' 16:31', $timezone),
        );
        $this->assertFalse($afterEnd->isAvailable());
        $this->assertSame('outside_working_hours', $afterEnd->getReason());
    }
}
