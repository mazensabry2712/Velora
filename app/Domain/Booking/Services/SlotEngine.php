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

    // ────────────────────────────────────────────────────────────────────────
    // Public API
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Get all available slots for a service+staff on a specific date.
     *
     * @param  Service      $service   The service being booked
     * @param  Staff        $staff     The staff member
     * @param  Carbon       $date      The date to check (time portion is ignored)
     * @param  string       $timezone  Client timezone (e.g. "Asia/Riyadh")
     * @return Collection<TimeSlot>
     */
    public function getAvailableSlots(
        Service $service,
        Staff   $staff,
        Carbon  $date,
        string  $timezone = 'UTC',
    ): Collection {
        $date = CarbonImmutable::instance($date)->startOfDay()->setTimezone($staff->timezone ?: 'UTC');

        // 1. Check business holiday
        if ($this->isHoliday($date, $staff)) {
            return collect();
        }

        // 2. Get working window for this day-of-week
        $workingWindow = $this->getWorkingWindow($staff, (int) $date->dayOfWeek, $date);

        if ($workingWindow === null) {
            return collect(); // staff doesn't work this day
        }

        [$windowStart, $windowEnd] = $workingWindow;

        // 3. Collect blocking intervals (breaks + time-off + existing bookings)
        $blocked = $this->getBlockedIntervals($staff, $date);

        // 4. Calculate total duration: service + buffers
        $serviceDuration = $service->duration_minutes ?: (int) $service->duration;
        $bufferBefore    = $service->buffer_before_minutes;
        $bufferAfter     = $service->buffer_after_minutes;

        // 5. Walk through the window in `slotIntervalMinutes` steps
        $slots  = collect();
        $cursor = $windowStart->copy()->addMinutes($bufferBefore);

        while ($cursor->copy()->addMinutes($serviceDuration)->lte($windowEnd)) {
            $slotStart         = $cursor->copy();
            $slotEnd           = $cursor->copy()->addMinutes($serviceDuration);
            $slotEndWithBuffer = $slotEnd->copy()->addMinutes($bufferAfter);

            // Effective blocking window includes buffer_before
            $blockStart = $slotStart->copy()->subMinutes($bufferBefore);

            if (! $this->overlapsAny($blockStart, $slotEndWithBuffer, $blocked)) {
                // Convert to requested timezone for the response
                $slots->push(new TimeSlot(
                    startsAt:         $slotStart->setTimezone($timezone),
                    endsAt:           $slotEnd->setTimezone($timezone),
                    endsAtWithBuffer: $slotEndWithBuffer->setTimezone($timezone),
                    isAvailable:      true,
                ));
            }

            $cursor->addMinutes($this->slotIntervalMinutes);
        }

        return $slots;
    }

    /**
     * Validate whether a specific start datetime is bookable.
     *
     * @param  Service   $service
     * @param  Staff     $staff
     * @param  Carbon    $startsAt       Proposed start (in any timezone)
     * @param  int|null  $excludeId      Appointment ID to exclude (for reschedules)
     */
    public function validateSlot(
        Service $service,
        Staff   $staff,
        Carbon  $startsAt,
        ?int    $excludeId  = null,
        ?int    $resourceId = null,
    ): SlotValidationResult {

        $tz        = $staff->timezone ?: 'UTC';
        $startLocal = $startsAt->clone()->setTimezone($tz);
        $date        = CarbonImmutable::instance($startLocal)->startOfDay();

        // 1. Date must be in the future
        if ($startLocal->isPast()) {
            return SlotValidationResult::unavailable('slot_in_the_past');
        }

        // 1b. Business rule: minimum advance notice (e.g. must book ≥ 2 h ahead)
        $minAdvanceHours = (int) BusinessRule::getValue(BusinessRule::MIN_ADVANCE_BOOKING_HOURS, 0);
        if ($minAdvanceHours > 0 && $startLocal->lt(now()->addHours($minAdvanceHours))) {
            return SlotValidationResult::unavailable('too_soon_to_book');
        }

        // 1c. Business rule: same-day booking toggle
        $allowSameDay = BusinessRule::getValue(BusinessRule::ALLOW_SAME_DAY_BOOKING, true);
        if (! $allowSameDay && $startLocal->isToday()) {
            return SlotValidationResult::unavailable('same_day_booking_not_allowed');
        }

        // 1d. Business rule: maximum advance booking window (e.g. no more than 60 days ahead)
        $maxAdvanceDays = (int) BusinessRule::getValue(BusinessRule::MAX_ADVANCE_BOOKING_DAYS, 0);
        if ($maxAdvanceDays > 0 && $startLocal->gt(now()->addDays($maxAdvanceDays)->endOfDay())) {
            return SlotValidationResult::unavailable('too_far_in_advance');
        }

        // 2. Holiday check
        if ($this->isHoliday($date, $staff)) {
            return SlotValidationResult::unavailable('holiday');
        }

        // 3. Working hours check
        // Use the requested appointment date, not today's date, when constructing
        // the working-hours bounds. Using today here incorrectly made all future
        // dates compare against a window anchored to the current day.
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

        // 4. Breaks / time-off / appointments check
        $blocked = $this->getBlockedIntervals($staff, $date, $excludeId);

        if ($this->overlapsAny($blockStart, $slotEndWithBuffer, $blocked)) {
            return SlotValidationResult::unavailable('slot_not_available');
        }

        // 5. Resource availability check
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

    // ────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Returns [Carbon $start, Carbon $end] in staff timezone, or null if not working.
     */
    private function getWorkingWindow(Staff $staff, int $dayOfWeek, ?CarbonImmutable $date = null): ?array
    {
        $tz = $staff->timezone ?: 'UTC';

        // Load working hours (eager-loaded or lazy)
        $hours = $staff->workingHours->firstWhere('day_of_week', $dayOfWeek);

        if (! $hours || ! $hours->is_working) {
            return null;
        }

        // Anchor the window to the date being evaluated. Falling back to today
        // keeps this helper safe for any existing internal callers.
        $baseDay = ($date ?: CarbonImmutable::now($tz)->startOfDay())->setTimezone($tz)->startOfDay();

        $start = $baseDay->copy()->setTimeFromTimeString($hours->start_time);
        $end   = $baseDay->copy()->setTimeFromTimeString($hours->end_time);

        return [$start, $end];
    }

    /**
     * Check if the given date is a business holiday for this staff member.
     */
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
     * Build a list of [start, end] Carbon pairs that are blocked for this staff/date.
     *
     * Blocked = recurring breaks + approved time-off + confirmed/pending appointments (with buffers).
     *
     * @return array<array{Carbon, Carbon}>
     */
    private function getBlockedIntervals(Staff $staff, CarbonImmutable $date, ?int $excludeAppointmentId = null): array
    {
        $tz      = $staff->timezone ?: 'UTC';
        $dayDate = $date->toDateString();
        $day     = (int) $date->dayOfWeek;
        $baseDay  = Carbon::parse($dayDate, $tz)->startOfDay();

        $blocked = [];

        // ── Recurring breaks ─────────────────────────────────────────────
        foreach ($staff->breaks->filter(fn($b) => $b->day_of_week === $day) as $break) {
            $blocked[] = [
                $baseDay->copy()->setTimeFromTimeString($break->start_time),
                $baseDay->copy()->setTimeFromTimeString($break->end_time),
            ];
        }

        // ── Approved time-off ────────────────────────────────────────────
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

        // ── Existing confirmed/pending appointments ───────────────────────
        $query = Appointment::query()
            ->where('staff_id_new', $staff->id)
            ->whereDate('starts_at', $dayDate)
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
     * Returns true if [proposedStart, proposedEnd) overlaps any blocked interval.
     *
     * @param  array<array{Carbon, Carbon}>  $blocked
     */
    private function overlapsAny(Carbon $start, Carbon $end, array $blocked): bool
    {
        foreach ($blocked as [$bStart, $bEnd]) {
            // Overlap condition: start < bEnd AND end > bStart
            if ($start->lt($bEnd) && $end->gt($bStart)) {
                return true;
            }
        }

        return false;
    }
}
