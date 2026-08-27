<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Illuminate\Validation\ValidationException;

final class UpdateAppointmentQueueState
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(int $appointmentId, string $status): mixed
    {
        $allowed = ['pending', 'confirmed', 'cancelled', 'completed'];

        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid appointment status.'],
            ]);
        }

        return $this->transactions->transaction(function () use ($appointmentId, $status) {
            $appointment = $this->appointments->findWithRelations($appointmentId, ['queue']);

            if (! $appointment) {
                throw ValidationException::withMessages([
                    'appointment' => ['Appointment not found.'],
                ]);
            }

            $this->appointments->update($appointment, ['status' => $status]);

            if ($appointment->queue) {
                $queueStatus = match ($status) {
                    'cancelled' => 'skipped',
                    'completed' => 'completed',
                    'confirmed' => $appointment->queue->status !== 'serving'
                        ? 'waiting'
                        : $appointment->queue->status,
                    default => $appointment->queue->status,
                };

                $appointment->queue->update(['status' => $queueStatus]);
            }

            return $appointment->load('queue');
        });
    }
}
