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
 * Generates future appointments from a recurring booking rule.
 */
class RecurringAppointmentService
{
    public function __construct(
        private readonly BookingCreationService $bookingService,
    ) {}

    /** @return Appointment[] */
    public function generateFromSeed(Appointment $seed, int $limit = 50): array
    {
        $rule = $seed->recurringRule;

        if (! $rule) {
            return [];
        }

        $created   = [];
        $current   = Carbon::parse($seed->starts_at);
        $generated = $rule->generated_count;

        while ($generated < $limit) {
            $next = $this->nextDate($current, $rule);

            if (! $next || $rule->hasReachedLimit()) {
                break;
            }

            if ($rule->ends_on && $next->greaterThan($rule->ends_on->endOfDay())) {
                break;
            }

            try {
                $appointment = $this->bookingService->create(new CreateBookingData(
                    serviceId:    $seed->service_id,
                    staffId:      $seed->staff_id_new,
                    startsAt:     $next,
                    timezone:     $seed->timezone ?? 'UTC',
                    customerId:   $seed->customer_id_new,
                    resourceId:   $seed->resource_id,
                    attendees:    $seed->attendees ?? 1,
                    source:       'recurring',
                    notes:        $seed->notes,
                    recurringId:  $rule->id,
                ));

                $created[] = $appointment;
                $generated++;
                $rule->increment('generated_count');
            } catch (\Throwable $e) {
                Log::info('RecurringService: slot unavailable for ' . $next->toDateTimeString() . ' — skipping.', [
                    'reason' => $e->getMessage(),
                ]);
            }

            $current = $next;

            if ($rule->max_occurrences && $generated >= $rule->max_occurrences) {
                break;
            }
        }

        return $created;
    }

    public function generateNext(RecurringRule $rule, Appointment $lastAppointment): ?Appointment
    {
        if ($rule->hasReachedLimit()) {
            return null;
        }

        $current = Carbon::parse($lastAppointment->starts_at);
        $next    = $this->nextDate($current, $rule);

        if (! $next || ($rule->ends_on && $next->greaterThan($rule->ends_on->endOfDay()))) {
            return null;
        }

        try {
            $appointment = $this->bookingService->create(new CreateBookingData(
                serviceId:    $lastAppointment->service_id,
                staffId:      $lastAppointment->staff_id_new,
                startsAt:     $next,
                timezone:     $lastAppointment->timezone ?? 'UTC',
                customerId:   $lastAppointment->customer_id_new,
                resourceId:   $lastAppointment->resource_id,
                attendees:    $lastAppointment->attendees ?? 1,
                source:       'recurring',
                notes:        $lastAppointment->notes,
                recurringId:  $rule->id,
            ));

            $rule->increment('generated_count');

            return $appointment;
        } catch (\Throwable $e) {
            Log::warning('RecurringService::generateNext failed: ' . $e->getMessage());
            return null;
        }
    }

    private function nextDate(Carbon $current, RecurringRule $rule): ?Carbon
    {
        $candidate = match ($rule->frequency) {
            'daily'   => $current->copy()->addDays($rule->interval),
            'weekly'  => $current->copy()->addWeeks($rule->interval),
            'monthly' => $current->copy()->addMonthsNoOverflow($rule->interval),
            default   => $current->copy()->addWeeks(1),
        };

        if ($rule->frequency === 'weekly' && ! empty($rule->days_of_week)) {
            $candidate = $this->nearestAllowedDay($current, $rule->days_of_week, $rule->interval);
            if (! $candidate) {
                return null;
            }
        }

        $candidate->setTime((int) $current->format('H'), (int) $current->format('i'), 0);

        return $candidate;
    }

    /** @param int[] $allowedDays */
    private function nearestAllowedDay(Carbon $from, array $allowedDays, int $intervalWeeks): ?Carbon
    {
        if (empty($allowedDays)) {
            return null;
        }

        $candidate = $from->copy()->addDay();
        $maxDays   = ($intervalWeeks * 7) + 7;

        for ($i = 0; $i < $maxDays; $i++, $candidate->addDay()) {
            if (in_array($candidate->dayOfWeek, $allowedDays, true)) {
                return $candidate->copy();
            }
        }

        return null;
    }
}
