<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Models\Appointment;
use App\Models\Queue;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Illuminate\Validation\ValidationException;

final class AddAppointmentToQueue
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(int $appointmentId, ?string $queueDate = null): Queue
    {
        return $this->transactions->transaction(function () use ($appointmentId, $queueDate): Queue {
            $appointment = $this->appointments->findWithRelations($appointmentId, ['customer', 'queue']);

            if (! $appointment) {
                throw ValidationException::withMessages(['appointment' => [__('Appointment not found.')] ]);
            }

            if ($appointment->queue) {
                throw ValidationException::withMessages(['queue' => [__('Already in queue.')] ]);
            }

            if (! $appointment->canBeAddedToQueue()) {
                throw ValidationException::withMessages(['queue' => [__('Cannot add to queue.')] ]);
            }

            $startsAt = $appointment->starts_at;
            if (! $startsAt) {
                throw ValidationException::withMessages(['appointment' => [__('Appointment start time is missing.')] ]);
            }

            if ($startsAt->lt(today())) {
                throw ValidationException::withMessages(['queue' => [__('Cannot queue past appointment.')] ]);
            }

            $queue = Queue::create([
                'appointment_id' => $appointment->id,
                'queue_number' => Queue::generateQueueNumber(),
                'queue_date' => $queueDate ?? $startsAt->toDateString(),
                'status' => 'waiting',
                'is_vip' => $appointment->customer->is_vip ?? false,
            ]);

            if ($appointment->status === Appointment::STATUS_PENDING) {
                $this->appointments->update($appointment, ['status' => Appointment::STATUS_CONFIRMED]);
            }

            return $queue;
        });
    }
}
