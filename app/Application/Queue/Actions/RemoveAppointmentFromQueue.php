<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Illuminate\Validation\ValidationException;

final class RemoveAppointmentFromQueue
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(int $appointmentId): mixed
    {
        return $this->transactions->transaction(function () use ($appointmentId) {
            $appointment = $this->appointments->findWithRelations($appointmentId, ['queue']);

            if (! $appointment) {
                throw ValidationException::withMessages([
                    'appointment' => ['Appointment not found.'],
                ]);
            }

            if (! $appointment->queue) {
                throw ValidationException::withMessages([
                    'queue' => [__('Not in queue.')],
                ]);
            }

            $queueStatus = $appointment->queue->status;
            $appointment->queue->delete();

            if ($queueStatus === 'completed') {
                $this->appointments->update($appointment, ['status' => 'completed']);
            } elseif (in_array($queueStatus, ['cancelled', 'skipped'], true)) {
                $this->appointments->update($appointment, ['status' => 'cancelled']);
            }

            return $appointment->fresh();
        });
    }
}
