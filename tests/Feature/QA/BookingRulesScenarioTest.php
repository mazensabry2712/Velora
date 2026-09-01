<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Application\Booking\Actions\CreatePublicBooking;
use App\Application\Booking\DTOs\PublicBookingData;
use App\Domain\Booking\Exceptions\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\BusinessRule;
use App\Models\Service;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class BookingRulesScenarioTest extends TenantTestCase
{
    private function prepareWorkingDay(): array
    {
        $timezone = $this->staff->timezone ?: config('app.timezone');
        $date = now($timezone)->addDay()->startOfDay();

        $this->service->forceFill([
            'is_active' => true,
            'is_online_bookable' => true,
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
        ])->save();

        \App\Models\StaffWorkingHours::create([
            'staff_id' => $this->staff->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_working' => true,
        ]);

        return [$date, $timezone];
    }

    private function bookingData(string $date, string $time = '09:00', ?int $serviceId = null, ?int $staffUserId = null): PublicBookingData
    {
        return new PublicBookingData(
            customerName: 'Booking Rules Customer',
            customerEmail: 'booking-rules@example.com',
            customerPhone: '+201000000020',
            serviceId: $serviceId ?? $this->service->id,
            staffUserId: $staffUserId ?? $this->staffMember->id,
            resourceId: null,
            appointmentDate: $date,
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
    public function inactive_service_is_rejected_before_a_public_booking_is_created(): void
    {
        [$date] = $this->prepareWorkingDay();

        $inactive = Service::create([
            'name' => 'Offline Service',
            'duration' => 30,
            'price' => 100,
            'is_active' => false,
            'is_online_bookable' => false,
        ]);

        $this->expectException(ValidationException::class);
        app(CreatePublicBooking::class)->execute($this->bookingData($date->toDateString(), '09:00', $inactive->id));
    }

    #[Test]
    public function staff_who_does_not_offer_the_service_is_rejected(): void
    {
        [$date] = $this->prepareWorkingDay();

        $secondStaffUser = \App\Models\User::create([
            'name' => 'Unassigned Staff',
            'email' => 'unassigned-staff@example.com',
            'password' => bcrypt('password'),
            'specialization' => 'General',
        ]);

        $secondStaff = \App\Models\Staff::create([
            'user_id' => $secondStaffUser->id,
            'first_name' => 'Unassigned',
            'last_name' => 'Staff',
            'email' => $secondStaffUser->email,
            'phone' => '0507654321',
            'accepts_bookings' => true,
            'is_active' => true,
        ]);

        \App\Models\StaffWorkingHours::create([
            'staff_id' => $secondStaff->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_working' => true,
        ]);

        $this->expectException(ValidationException::class);
        app(CreatePublicBooking::class)->execute($this->bookingData($date->toDateString(), '09:00', null, $secondStaffUser->id));
    }

    #[Test]
    public function outside_working_hours_is_rejected_by_the_slot_engine(): void
    {
        [$date] = $this->prepareWorkingDay();
        $this->assertSlotUnavailableWithReason(
            fn () => app(CreatePublicBooking::class)->execute($this->bookingData($date->toDateString(), '12:00')),
            'outside_working_hours',
        );
    }

    #[Test]
    public function the_same_staff_slot_cannot_be_booked_twice(): void
    {
        [$date, $timezone] = $this->prepareWorkingDay();

        $action = app(CreatePublicBooking::class);
        $action->execute($this->bookingData($date->toDateString(), '09:00'));

        $this->assertSlotUnavailableWithReason(
            fn () => $action->execute(new PublicBookingData(
                customerName: 'Second Booking Customer',
                customerEmail: 'second-booking@example.com',
                customerPhone: '+201000000021',
                serviceId: $this->service->id,
                staffUserId: $this->staffMember->id,
                resourceId: null,
                appointmentDate: $date->toDateString(),
                appointmentTime: '09:00',
                requestedTimezone: $timezone,
                notes: null,
            )),
            'slot_not_available',
        );

        $this->assertSame(1, Appointment::whereDate('date', $date->toDateString())->count());
    }

    #[Test]
    public function configured_minimum_advance_notice_is_enforced(): void
    {
        [$date] = $this->prepareWorkingDay();

        BusinessRule::setValue(BusinessRule::MIN_ADVANCE_BOOKING_HOURS, 48, 'integer');

        $this->assertSlotUnavailableWithReason(
            fn () => app(CreatePublicBooking::class)->execute($this->bookingData($date->toDateString(), '09:00')),
            'too_soon_to_book',
        );
    }
}
