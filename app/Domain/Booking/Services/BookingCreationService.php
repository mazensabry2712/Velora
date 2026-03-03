<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\DTOs\CreateBookingData;
use App\Domain\Booking\Events\AppointmentCreated;
use App\Models\Appointment;
use App\Models\AppointmentStatusHistory;
use App\Models\BusinessRule;
use App\Models\PaymentTransaction;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

/**
 * BookingCreationService — orchestrates the creation of a new appointment.
 *
 * Uses a DB transaction with pessimistic locking to prevent double-booking
 * under concurrent requests.
 *
 * @throws \App\Domain\Booking\Exceptions\SlotUnavailableException
 * @throws \App\Domain\Booking\Exceptions\ServiceNotFoundException
 * @throws \App\Domain\Booking\Exceptions\StaffNotFoundException
 */
class BookingCreationService
{
    public function __construct(
        private readonly SlotEngine $slotEngine,
    ) {}

    /**
     * Create a new appointment.
     *
     * Flow:
     *   1. Load service & staff (fail fast if not found)
     *   2. Begin DB transaction
     *   3. LOCK staff's appointments for the slot window (pessimistic)
     *   4. Re-validate slot availability inside the lock
     *   5. Create appointment record
     *   6. Write initial status history entry
     *   7. Commit & fire AppointmentCreated event
     */
    public function create(CreateBookingData $data): Appointment
    {
        $service = Service::findOrFail($data->serviceId);
        $staff   = Staff::with(['workingHours', 'breaks', 'timeOff'])->findOrFail($data->staffId);

        return DB::transaction(function () use ($data, $service, $staff) {

            // ── Pessimistic lock: prevent concurrent double-booking ───────
            // Lock all appointments for this staff in the target time window.
            $serviceDuration = $service->duration_minutes ?: (int) $service->duration;
            $bufferAfter     = $service->buffer_after_minutes;
            $bufferBefore    = $service->buffer_before_minutes;

            $lockStart = $data->startsAt->copy()->subMinutes($bufferBefore)->subMinutes($serviceDuration + $bufferAfter);
            $lockEnd   = $data->startsAt->copy()->addMinutes($serviceDuration + $bufferAfter + $bufferBefore);

            Appointment::query()
                ->where('staff_id_new', $staff->id)
                ->where('starts_at', '<', $lockEnd)
                ->where('ends_at_with_buffer', '>', $lockStart)
                ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
                ->lockForUpdate()
                ->get(['id']); // fetch IDs to hold the lock

            // ── Re-validate slot with the lock held ───────────────────────
            $result = $this->slotEngine->validateSlot(
                $service,
                $staff,
                $data->startsAt,
                resourceId: $data->resourceId,
            );

            if (! $result->isAvailable()) {
                throw new \App\Domain\Booking\Exceptions\SlotUnavailableException(
                    $result->getReason()
                );
            }

            // ── Business rule: max bookings per customer per day ──────────
            if ($data->customerId) {
                $maxPerDay = (int) BusinessRule::getValue(BusinessRule::MAX_BOOKINGS_PER_CUSTOMER_PER_DAY, 0);
                if ($maxPerDay > 0) {
                    $dayCount = Appointment::query()
                        ->where('customer_id_new', $data->customerId)
                        ->whereDate('starts_at', $data->startsAt->toDateString())
                        ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
                        ->count();
                    if ($dayCount >= $maxPerDay) {
                        throw new \App\Domain\Booking\Exceptions\SlotUnavailableException(
                            'max_bookings_per_day_reached'
                        );
                    }
                }
            }

            // ── Calculate time bounds ─────────────────────────────────────
            $startsAt           = $data->startsAt->clone()->utc();
            $endsAt             = $startsAt->copy()->addMinutes($serviceDuration);
            $endsAtWithBuffer   = $endsAt->copy()->addMinutes($bufferAfter);

            // ── Create the appointment ────────────────────────────────────
            $appointment = Appointment::create([
                // New booking engine fields
                'customer_id_new'     => $data->customerId,
                'staff_id_new'        => $staff->id,
                'service_id'          => $service->id,
                'resource_id'         => $data->resourceId,
                'recurring_id'        => $data->recurringId,
                'starts_at'           => $startsAt,
                'ends_at'             => $endsAt,
                'ends_at_with_buffer' => $endsAtWithBuffer,
                'timezone'            => $data->timezone,
                'price'               => $service->price,
                'deposit_amount'      => $service->deposit_amount ?? 0,
                'attendees'           => $data->attendees,
                'source'              => $data->source,
                'notes'               => $data->notes,
                'status'              => Appointment::STATUS_PENDING,

                // Legacy fields (backward compat)
                'customer_id'    => $data->legacyCustomerId,
                'staff_id'       => $staff->user_id,
                'date'           => $startsAt->toDateString(),
                'time_slot'      => $startsAt->format('H:i'),
                'service_type'   => $service->name,
            ]);

            // ── Write initial status history entry ───────────────────────
            AppointmentStatusHistory::create([
                'appointment_id' => $appointment->id,
                'from_status'    => null,
                'to_status'      => Appointment::STATUS_PENDING,
                'actor_type'     => $data->source,
                'reason'         => 'Booking created',
            ]);

            // ── Log deposit PaymentTransaction (if required) ─────────────
            $depositCents = (int) round(($appointment->deposit_amount ?? 0) * 100);
            if ($depositCents > 0) {
                PaymentTransaction::create([
                    'appointment_id' => $appointment->id,
                    'customer_id'    => $data->customerId,
                    'gateway'        => 'none',
                    'type'           => 'deposit',
                    'status'         => 'pending',
                    'amount'         => $depositCents,
                    'currency'       => config('app.currency', 'USD'),
                ]);
            }

            // ── Dispatch event (listeners handle reminders, notifications) ─
            AppointmentCreated::dispatch($appointment);

            return $appointment;
        });
    }
}
