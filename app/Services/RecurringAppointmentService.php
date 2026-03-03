<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Booking\DTOs\CreateBookingData;
use App\Domain\Booking\Services\BookingCreationService;
use App\Models\Appointment;
use App\Models\RecurringRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * RecurringAppointmentService
 *
 * Given a "seed" appointment and its RecurringRule, generates the next
 * N occurrences by projecting the slot forward according to the rule's
 * frequency and days_of_week pattern.
 *
 * Usage:
 *   RecurringAppointmentService::generateFromSeed($firstAppointment);
 *   RecurringAppointmentService::generateNext($rule, $lastAppointment);
 */
class RecurringAppointmentService
{
    public function __construct(
        private readonly BookingCreationService $bookingService,
    ) {}

    /**
     * Generate all remaining occurrences for a recurring series.
     *
     * @param  Appointment     $seed   The first appointment in the series.
     * @param  int             $limit  Safety cap — max occurrences to create.
     * @return Appointment[]          Newly created appointments.
     */
    public function generateFromSeed(Appointment $seed, int $limit = 50): array
    {
        $rule = $seed->recurringRule;

        if (! $rule) {
            return [];
        }

        $created    = [];
        $current    = Carbon::parse($seed->starts_at);
        $generated  = $rule->generated_count; // already includes the seed

        while (true) {
            if ($generated >= $limit) {
                break;
            }

            $next = $this->nextDate($current, $rule);

            if (! $next) {
                break;
            }

            if ($rule->hasReachedLimit()) {
                break;
            }

            // Stop if past the ends_on date
            if ($rule->ends_on && $next->greaterThan($rule->ends_on->endOfDay())) {
                break;
            }

            try {
                $appointment = $this->bookingService->create(new CreateBookingData(
                    serviceId:        $seed->service_id,
                    staffId:          $seed->staff_id_new,
                    startsAt:         $next,
                    timezone:         $seed->timezone ?? 'UTC',
                    customerId:       $seed->customer_id_new,
                    legacyCustomerId: $seed->customer_id,
                    resourceId:       $seed->resource_id,
                    attendees:        $seed->attendees ?? 1,
                    source:           'recurring',
                    notes:            $seed->notes,
                    recurringId:      $rule->id,
                ));

                $created[] = $appointment;
                $generated++;

                $rule->increment('generated_count');

            } catch (\Throwable $e) {
                // Slot unavailable → skip to next occurrence
                Log::info("RecurringService: slot unavailable for " . $next->toDateTimeString() . " — skipping. Reason: " . $e->getMessage());
            }

            $current = $next;

            if ($rule->max_occurrences && $generated >= $rule->max_occurrences) {
                break;
            }
        }

        return $created;
    }

    /**
     * Generate only the *next* single occurrence after $lastAppointment.
     * Used by a queue Job to lazily extend the series.
     */
    public function generateNext(RecurringRule $rule, Appointment $lastAppointment): ?Appointment
    {
        if ($rule->hasReachedLimit()) {
            return null;
        }

        $current = Carbon::parse($lastAppointment->starts_at);
        $next    = $this->nextDate($current, $rule);

        if (! $next) {
            return null;
        }

        if ($rule->ends_on && $next->greaterThan($rule->ends_on->endOfDay())) {
            return null;
        }

        try {
            $appointment = $this->bookingService->create(new CreateBookingData(
                serviceId:        $lastAppointment->service_id,
                staffId:          $lastAppointment->staff_id_new,
                startsAt:         $next,
                timezone:         $lastAppointment->timezone ?? 'UTC',
                customerId:       $lastAppointment->customer_id_new,
                legacyCustomerId: $lastAppointment->customer_id,
                resourceId:       $lastAppointment->resource_id,
                attendees:        $lastAppointment->attendees ?? 1,
                source:           'recurring',
                notes:            $lastAppointment->notes,
                recurringId:      $rule->id,
            ));

            $rule->increment('generated_count');

            return $appointment;

        } catch (\Throwable $e) {
            Log::warning("RecurringService::generateNext failed: " . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Date projection helpers
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Calculate the next occurrence date from $current for the given rule.
     * Returns null if no valid next date can be found within a safety window.
     */
    private function nextDate(Carbon $current, RecurringRule $rule): ?Carbon
    {
        $candidate = $current->copy();

        // Advance at least one interval unit
        $candidate = match ($rule->frequency) {
            'daily'   => $candidate->addDays($rule->interval),
            'weekly'  => $candidate->addWeeks($rule->interval),
            'monthly' => $candidate->addMonthsNoOverflow($rule->interval),
            default   => $candidate->addWeeks(1),
        };

        // For weekly rules with explicit days_of_week, find the nearest matching day
        if ($rule->frequency === 'weekly' && ! empty($rule->days_of_week)) {
            $candidate = $this->nearestAllowedDay($current, $rule->days_of_week, $rule->interval);
            if (! $candidate) {
                return null;
            }
        }

        // Preserve original time
        $candidate->setTime(
            (int) $current->format('H'),
            (int) $current->format('i'),
            0
        );

        return $candidate;
    }

    /**
     * Find the next date (after $from) that falls on one of $allowedDays
     * within the interval constraint. Days: 0=Sunday … 6=Saturday.
     *
     * @param  int[]  $allowedDays
     */
    private function nearestAllowedDay(Carbon $from, array $allowedDays, int $intervalWeeks): ?Carbon
    {
        if (empty($allowedDays)) {
            return null;
        }

        // Scan up to interval*7 + 7 days to find the next allowed day
        $maxDays = ($intervalWeeks * 7) + 7;
        $candidate = $from->copy()->addDay();

        for ($i = 0; $i < $maxDays; $i++, $candidate->addDay()) {
            if (in_array($candidate->dayOfWeek, $allowedDays, true)) {
                return $candidate->copy();
            }
        }

        return null;
    }
}
