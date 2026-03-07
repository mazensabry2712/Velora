<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Appointment;
use App\Models\StaffCommission;
use App\Models\TenantSubscription;
use App\Models\UsageLog;
use App\Jobs\GenerateNextRecurringAppointment;
use App\Jobs\NotifyWaitingListOnAvailability;
use Illuminate\Support\Facades\Log;

/**
 * AppointmentObserver
 *
 * Automatically creates a StaffCommission record when an appointment transitions
 * to "completed" status, provided the assigned staff member has a commission
 * type and value configured.
 *
 * Registration: AppServiceProvider::boot() or config/app.php observers.
 */
class AppointmentObserver
{
    /**
     * Handle the Appointment "updated" event.
     * Fires a commission only on the first transition to STATUS_COMPLETED.
     */
    public function updated(Appointment $appointment): void
    {
        // Only react to status changes
        if (! $appointment->wasChanged('status')) {
            return;
        }

        // → completed: create staff commission + check Aha Moment
        if ($appointment->status === Appointment::STATUS_COMPLETED) {
            $this->createCommission($appointment);
            $this->checkAhaMoment($appointment);
        }

        // → confirmed or completed in a recurring series: generate next occurrence
        if (in_array($appointment->status, [Appointment::STATUS_CONFIRMED, Appointment::STATUS_COMPLETED], true)
            && $appointment->recurring_id) {
            GenerateNextRecurringAppointment::dispatch(
                $appointment->id,
                $appointment->recurring_id,
            );
        }

        // → cancelled: notify waiting list that a slot opened up
        if ($appointment->status === Appointment::STATUS_CANCELLED) {
            $this->notifyWaitingList($appointment);
        }
    }

    // ─────────────────────────────────────────────────────────────────────

    private function notifyWaitingList(Appointment $appointment): void
    {
        if (! $appointment->service_id) {
            return;
        }

        NotifyWaitingListOnAvailability::dispatch(
            $appointment->service_id,
            $appointment->staff_id_new,
            $appointment->date ? (string) $appointment->date : null,
        );
    }

    // ─────────────────────────────────────────────────────────────────────

    /**
     * Detect the "Aha Moment": ≥5 completed appointments reached during trial.
     * Marks aha_reached on TenantSubscription (central DB) the first time.
     */
    private function checkAhaMoment(Appointment $appointment): void
    {
        try {
            $completedCount = Appointment::where('status', Appointment::STATUS_COMPLETED)->count();

            if ($completedCount < 5) {
                return;
            }

            $tenantId = tenant('id');
            if (! $tenantId) {
                return;
            }

            $sub = TenantSubscription::where('tenant_id', $tenantId)->first();

            if (! $sub || $sub->aha_reached) {
                return; // already flagged or no subscription record
            }

            $sub->update([
                'aha_reached'    => true,
                'aha_reached_at' => now(),
            ]);

            UsageLog::log('aha_moment_reached', [
                'tenant_id'       => $tenantId,
                'completed_count' => $completedCount,
                'trial_days_left' => $sub->trialDaysLeft(),
            ]);

            Log::info("AppointmentObserver: Aha Moment reached for tenant [{$tenantId}] ({$completedCount} completed).");
        } catch (\Throwable $e) {
            Log::error('AppointmentObserver.checkAhaMoment: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────

    private function createCommission(Appointment $appointment): void
    {
        try {
            // Resolve the Staff model (uses staff_id_new column)
            $staff = $appointment->staffMember; // BelongsTo Staff via staff_id_new

            if (! $staff) {
                return;
            }
            if (! $staff->commission_type || ! $staff->commission_value) {
                return;
            }

            // Avoid duplicate commission for the same appointment
            if (StaffCommission::where('appointment_id', $appointment->id)->exists()) {
                return;
            }

            // Gross amount in cents (appointment->price is decimal)
            $grossCents = (int) round((float) $appointment->price * 100);

            if ($grossCents <= 0) {
                return;
            }

            $commissionCents = match ($staff->commission_type) {
                'percentage' => (int) round($grossCents * ($staff->commission_value / 100)),
                'fixed'      => (int) round((float) $staff->commission_value * 100),
                default      => 0,
            };

            if ($commissionCents <= 0) {
                return;
            }

            StaffCommission::create([
                'staff_id'          => $staff->id,
                'appointment_id'    => $appointment->id,
                'transaction_id'    => null,
                'gross_amount'      => $grossCents,
                'commission_type'   => $staff->commission_type,
                'commission_rate'   => $staff->commission_value,
                'commission_amount' => $commissionCents,
                'currency'          => 'USD',
                'is_paid'           => false,
                'metadata'          => [
                    'auto_created' => true,
                    'created_by'   => 'AppointmentObserver',
                ],
            ]);

            Log::info("AppointmentObserver: Commission created for Staff #{$staff->id}, Appointment #{$appointment->id}, Amount: {$commissionCents} cents");
        } catch (\Throwable $e) {
            Log::error("AppointmentObserver: Failed to create commission for Appointment #{$appointment->id}: " . $e->getMessage());
        }
    }
}
