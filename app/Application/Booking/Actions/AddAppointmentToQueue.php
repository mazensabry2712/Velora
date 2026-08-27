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

            if ($appointment->date->lt(today())) {
                throw ValidationException::withMessages(['queue' => [__('Cannot queue past appointment.')] ]);
            }

            $queue = Queue::create([
                'appointment_id' => $appointment->id,
                'queue_number' => Queue::generateQueueNumber(),
                'queue_date' => $queueDate ?? $appointment->date->format('Y-m-d'),
                'status' => 'waiting',
                'is_vip' => $appointment->customer->is_vip ?? false,
            ]);

            if ($appointment->status === 'pending') {
                $this->appointments->update($appointment, ['status' => 'confirmed']);
            }

            return $queue;
        });
    }
}
