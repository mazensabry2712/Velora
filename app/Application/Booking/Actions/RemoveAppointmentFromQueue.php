<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Models\Appointment;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class RemoveAppointmentFromQueue
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(int $appointmentId): Appointment
    {
        return $this->transactions->transaction(function () use ($appointmentId): Appointment {
            $appointment = $this->appointments->findWithRelations($appointmentId, ['queue']);

            if (! $appointment) {
                throw (new ModelNotFoundException())->setModel(Appointment::class, [$appointmentId]);
            }

            if (! $appointment->queue) {
                throw ValidationException::withMessages([
                    'queue' => [__('Not in queue.')],
                ]);
            }

            $queueStatus = (string) $appointment->queue->status;
            $appointment->queue->delete();

            if ($queueStatus === 'completed') {
                $this->appointments->update($appointment, ['status' => 'completed']);
            } elseif (in_array($queueStatus, ['cancelled', 'skipped'], true)) {
                $this->appointments->update($appointment, ['status' => 'cancelled']);
            }

            return $appointment->fresh(['customer', 'staff', 'service', 'queue']);
        });
    }
}
