<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\RecurringRule;
use App\Services\RecurringAppointmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * GenerateNextRecurringAppointment
 *
 * When a recurring appointment is completed or confirmed, this Job creates
 * the *next* occurrence in the series so the calendar stays populated
 * one appointment ahead.
 *
 * Dispatched by: AppointmentObserver when status → confirmed/completed and
 *               appointment belongs to a recurring series.
 */
class GenerateNextRecurringAppointment implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly int $appointmentId,
        private readonly int $recurringRuleId,
    ) {}

    public function handle(RecurringAppointmentService $recurringService): void
    {
        $rule        = RecurringRule::find($this->recurringRuleId);
        $appointment = Appointment::find($this->appointmentId);

        if (! $rule || ! $appointment) {
            Log::warning("GenerateNextRecurring: missing rule or appointment (rule #{$this->recurringRuleId}, appt #{$this->appointmentId})");
            return;
        }

        if ($rule->hasReachedLimit()) {
            Log::info("GenerateNextRecurring: rule #{$rule->id} has reached its limit — no new occurrence.");
            return;
        }

        $next = $recurringService->generateNext($rule, $appointment);

        if ($next) {
            Log::info("GenerateNextRecurring: created Appointment #{$next->id} for rule #{$rule->id}");
        } else {
            Log::info("GenerateNextRecurring: no slot available for rule #{$rule->id}");
        }
    }
}
