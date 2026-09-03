<?php

declare(strict_types=1);

namespace App\Domain\Booking\Services;

use App\Application\Shared\Contracts\TransactionManager;
use App\Domain\Booking\DTOs\CreateBookingData;
use App\Domain\Booking\Events\AppointmentCreated;
use App\Models\Appointment;
use App\Models\AppointmentStatusHistory;
use App\Models\BusinessRule;
use App\Models\PaymentTransaction;
use App\Models\Service;
use App\Models\Staff;

/** BookingCreationService — domain/application orchestration for appointment creation. */
final class BookingCreationService
{
    public function __construct(
        private readonly SlotEngine $slotEngine,
        private readonly TransactionManager $transactions,
    ) {}

    /** Create a new appointment with concurrency-safe slot validation. */
    public function create(CreateBookingData $data): Appointment
    {
        $service = Service::findOrFail($data->serviceId);
        $staff   = Staff::with(['workingHours', 'breaks', 'timeOff'])->findOrFail($data->staffId);

        return $this->transactions->transaction(function () use ($data, $service, $staff) {
            $serviceDuration = $service->duration_minutes ?: (int) $service->duration;
            $bufferAfter     = $service->buffer_after_minutes;
            $bufferBefore    = $service->buffer_before_minutes;

            // `starts_at` / `ends_at*` are persisted in UTC. The input and
            // legacy display fields (`date`, `time_slot`) remain in the
            // staff/tenant timezone supplied by the booking boundary.
            $lockStart = $data->startsAt->copy()
                ->subMinutes($bufferBefore)
                ->subMinutes($serviceDuration + $bufferAfter)
                ->utc();
            $lockEnd = $data->startsAt->copy()
                ->addMinutes($serviceDuration + $bufferAfter + $bufferBefore)
                ->utc();

            Appointment::query()
                ->where('staff_id_new', $staff->id)
                ->where('starts_at', '<', $lockEnd)
                ->where('ends_at_with_buffer', '>', $lockStart)
                ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
                ->lockForUpdate()
                ->get(['id']);

            $result = $this->slotEngine->validateSlot(
                $service,
                $staff,
                $data->startsAt,
                resourceId: $data->resourceId,
            );

            if (! $result->isAvailable()) {
                throw new \App\Domain\Booking\Exceptions\SlotUnavailableException($result->getReason());
            }

            if ($data->customerId) {
                $maxPerDay = (int) BusinessRule::getValue(BusinessRule::MAX_BOOKINGS_PER_CUSTOMER_PER_DAY, 0);

                if ($maxPerDay > 0) {
                    $dayCount = Appointment::query()
                        ->where('customer_id_new', $data->customerId)
                        ->whereDate('starts_at', $data->startsAt->copy()->utc()->toDateString())
                        ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
                        ->count();

                    if ($dayCount >= $maxPerDay) {
                        throw new \App\Domain\Booking\Exceptions\SlotUnavailableException('max_bookings_per_day_reached');
                    }
                }
            }

            $localStartsAt = $data->startsAt->copy();
            $startsAt = $localStartsAt->copy()->utc();
            $endsAt = $startsAt->copy()->addMinutes($serviceDuration);
            $endsAtWithBuffer = $endsAt->copy()->addMinutes($bufferAfter);

            $appointment = Appointment::create([
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
                'date'                => $localStartsAt->toDateString(),
                'time_slot'           => $localStartsAt->format('H:i'),
                'service_type'        => $service->name,
            ]);

            AppointmentStatusHistory::create([
                'appointment_id' => $appointment->id,
                'from_status'    => null,
                'to_status'      => Appointment::STATUS_PENDING,
                'actor_type'     => $data->source,
                'reason'         => 'Booking created',
            ]);

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

            AppointmentCreated::dispatch($appointment);

            return $appointment;
        });
    }
}
