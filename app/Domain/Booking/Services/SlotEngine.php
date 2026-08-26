<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\DTOs\SlotValidationResult;
use App\Domain\Booking\DTOs\TimeSlot;
use App\Models\Appointment;
use App\Models\BusinessRule;
use App\Models\Holiday;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * SlotEngine — core availability engine for the Velora booking system.
 *
 * Responsibilities:
 *   - Generate available time slots for a service+staff on a given date
 *   - Validate whether a specific datetime slot is bookable
 *   - Account for: working hours, breaks, time-off, holidays, existing bookings
 *
 * All times are handled in the staff member's timezone (or business timezone)
 * and returned in the requested timezone.
 */
class SlotEngine
{
    public function __construct(
        private readonly int $slotIntervalMinutes = 15,
    ) {}

    public function getAvailableSlots(
        Service $service,
        Staff   $staff,
        Carbon  $date,
        string  $timezone = 'UTC',
    ): Collection {
        $date = CarbonImmutable::instance($date)->startOfDay()->setTimezone($staff->timezone ?: 'UTC');

        if ($this->isHoliday($date, $staff)) {
            return collect();
        }

        $workingWindow = $this->getWorkingWindow($staff, (int) $date->dayOfWeek, $date);

        if ($workingWindow === null) {
            return collect();
        }

        [$windowStart, $windowEnd] = $workingWindow;
        $blocked = $this->getBlockedIntervals($staff, $date);

        $serviceDuration = $service->duration_minutes ?: (int) $service->duration;
        $bufferBefore    = $service->buffer_before_minutes;
        $bufferAfter     = $service->buffer_after_minutes;

        $slots  = collect();
        $cursor = $windowStart->addMinutes($bufferBefore);

        while ($cursor->addMinutes($serviceDuration)->lte($windowEnd)) {
            $slotStart         = $cursor;
            $slotEnd           = $cursor->addMinutes($serviceDuration);
            $slotEndWithBuffer = $slotEnd->addMinutes($bufferAfter);
            $blockStart        = $slotStart->subMinutes($bufferBefore);

            if (! $this->overlapsAny($blockStart, $slotEndWithBuffer, $blocked)) {
                // TimeSlot's public DTO contract uses mutable Carbon instances.
                // Convert explicitly after timezone conversion so CarbonImmutable
                // from the availability engine never leaks into the DTO boundary.
                $slots->push(new TimeSlot(
                    startsAt: Carbon::instance($slotStart->setTimezone($timezone)),
                    endsAt: Carbon::instance($slotEnd->setTimezone($timezone)),
                    endsAtWithBuffer: Carbon::instance($slotEndWithBuffer->setTimezone($timezone)),
                    isAvailable: true,
                ));
            }

            $cursor = $cursor->addMinutes($this->slotIntervalMinutes);
        }

        return $slots;
    }

    public function validateSlot(
        Service $service,
        Staff   $staff,
        Carbon  $startsAt,
        ?int    $excludeId  = null,
        ?int    $resourceId = null,
    ): SlotValidationResult {
        $tz         = $staff->timezone ?: 'UTC';
        $startLocal = $startsAt->clone()->setTimezone($tz);
        $date       = CarbonImmutable::instance($startLocal)->startOfDay();

        if ($startLocal->isPast()) {
            return SlotValidationResult::unavailable('slot_in_the_past');
        }

        $minAdvanceHours = (int) BusinessRule::getValue(BusinessRule::MIN_ADVANCE_BOOKING_HOURS, 0);
        if ($minAdvanceHours > 0 && $startLocal->lt(now()->addHours($minAdvanceHours))) {
            return SlotValidationResult::unavailable('too_soon_to_book');
        }

        $allowSameDay = BusinessRule::getValue(BusinessRule::ALLOW_SAME_DAY_BOOKING, true);
        if (! $allowSameDay && $startLocal->isToday()) {
            return SlotValidationResult::unavailable('same_day_booking_not_allowed');
        }

        $maxAdvanceDays = (int) BusinessRule::getValue(BusinessRule::MAX_ADVANCE_BOOKING_DAYS, 0);
        if ($maxAdvanceDays > 0 && $startLocal->gt(now()->addDays($maxAdvanceDays)->endOfDay())) {
            return SlotValidationResult::unavailable('too_far_in_advance');
        }

        if ($this->isHoliday($date, $staff)) {
            return SlotValidationResult::unavailable('holiday');
        }

        $workingWindow = $this->getWorkingWindow($staff, (int) $date->dayOfWeek, $date);
        if ($workingWindow === null) {
            return SlotValidationResult::unavailable('staff_not_working_this_day');
        }

        [$windowStart, $windowEnd] = $workingWindow;

        $serviceDuration = $service->duration_minutes ?: (int) $service->duration;
        $bufferAfter     = $service->buffer_after_minutes;
        $bufferBefore    = $service->buffer_before_minutes;

        $slotEnd           = $startLocal->copy()->addMinutes($serviceDuration);
        $slotEndWithBuffer = $slotEnd->copy()->addMinutes($bufferAfter);
        $blockStart        = $startLocal->copy()->subMinutes($bufferBefore);

        if ($startLocal->lt($windowStart) || $slotEndWithBuffer->gt($windowEnd)) {
            return SlotValidationResult::unavailable('outside_working_hours');
        }

        $blocked = $this->getBlockedIntervals($staff, $date, $excludeId);

        if ($this->overlapsAny($blockStart, $slotEndWithBuffer, $blocked)) {
            return SlotValidationResult::unavailable('slot_not_available');
        }

        if ($resourceId !== null) {
            $resourceConflict = Appointment::query()
                ->where('resource_id', $resourceId)
                ->where('starts_at', '<', $slotEndWithBuffer->utc())
                ->where('ends_at_with_buffer', '>', $startsAt->clone()->utc())
                ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
                ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            if ($resourceConflict) {
                return SlotValidationResult::unavailable('resource_not_available');
            }
        }

        return SlotValidationResult::available();
    }

    private function getWorkingWindow(Staff $staff, int $dayOfWeek, ?CarbonImmutable $date = null): ?array
    {
        $tz = $staff->timezone ?: 'UTC';
        $hours = $staff->workingHours->firstWhere('day_of_week', $dayOfWeek);

        if (! $hours || ! $hours->is_working) {
            return null;
        }

        $baseDay = ($date ?: CarbonImmutable::now($tz)->startOfDay())->setTimezone($tz)->startOfDay();

        $start = $baseDay->setTimeFromTimeString($hours->start_time);
        $end   = $baseDay->setTimeFromTimeString($hours->end_time);

        return [$start, $end];
    }

    private function isHoliday(CarbonImmutable $date, Staff $staff): bool
    {
        return Holiday::query()
            ->where('date', $date->toDateString())
            ->where(function ($q) use ($staff) {
                $q->where('applies_to_all', true)
                  ->orWhereHas('staff', fn($s) => $s->where('staff_id', $staff->id));
            })
            ->exists();
    }

    /**
     * @return array<array{Carbon, Carbon}>
     */
    private function getBlockedIntervals(Staff $staff, CarbonImmutable $date, ?int $excludeAppointmentId = null): array
    {
        $tz       = $staff->timezone ?: 'UTC';
        $day      = (int) $date->dayOfWeek;
        $baseDay  = Carbon::parse($date->toDateString(), $tz)->startOfDay();
        $blocked  = [];

        foreach ($staff->breaks->filter(fn($b) => $b->day_of_week === $day) as $break) {
            $blocked[] = [
                $baseDay->copy()->setTimeFromTimeString($break->start_time),
                $baseDay->copy()->setTimeFromTimeString($break->end_time),
            ];
        }

        foreach ($staff->timeOff->where('status', 'approved') as $off) {
            if ($off->start_date->gt($date) || $off->end_date->lt($date)) {
                continue;
            }

            if ($off->all_day) {
                $blocked[] = [
                    $baseDay->copy()->startOfDay(),
                    $baseDay->copy()->endOfDay(),
                ];
            } else {
                $blocked[] = [
                    $baseDay->copy()->setTimeFromTimeString($off->start_time),
                    $baseDay->copy()->setTimeFromTimeString($off->end_time),
                ];
            }
        }

        // Appointment timestamps are persisted in UTC. Query the complete local
        // business day by UTC overlap rather than comparing the UTC date string.
        // This keeps conflict detection correct for non-UTC staff timezones.
        $dayStartUtc = $baseDay->copy()->utc();
        $dayEndUtc   = $baseDay->copy()->endOfDay()->utc();

        $query = Appointment::query()
            ->where('staff_id_new', $staff->id)
            ->where('starts_at', '<=', $dayEndUtc)
            ->where(function ($q) use ($dayStartUtc) {
                $q->where('ends_at_with_buffer', '>=', $dayStartUtc)
                  ->orWhere('ends_at', '>=', $dayStartUtc);
            })
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW]);

        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }

        foreach ($query->get(['starts_at', 'ends_at_with_buffer', 'ends_at']) as $appt) {
            $end = $appt->ends_at_with_buffer ?? $appt->ends_at;

            if ($appt->starts_at && $end) {
                $blocked[] = [
                    Carbon::instance($appt->starts_at)->setTimezone($tz),
                    Carbon::instance($end)->setTimezone($tz),
                ];
            }
        }

        return $blocked;
    }

    /**
     * @param array<array{CarbonInterface, CarbonInterface}> $blocked
     */
    private function overlapsAny(CarbonInterface $start, CarbonInterface $end, array $blocked): bool
    {
        foreach ($blocked as [$bStart, $bEnd]) {
            if ($start->lt($bEnd) && $end->gt($bStart)) {
                return true;
            }
        }

        return false;
    }
}
