<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Domain\Booking\Rules\AppointmentStatusTransition;
use App\Models\Appointment;
use App\Models\Queue;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class ChangeAppointmentStatus
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly AppointmentStatusTransition $transitions,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(int $appointmentId, string $status): Appointment
    {
        return $this->transactions->transaction(function () use ($appointmentId, $status): Appointment {
            $appointment = $this->appointments->findWithRelations($appointmentId, ['queue']);

            if (! $appointment) {
                throw (new ModelNotFoundException())->setModel(Appointment::class, [$appointmentId]);
            }

            $this->transitions->assertAllowed((string) $appointment->status, $status);

            $now = now();
            $lifecycleTimestamps = match ($status) {
                Appointment::STATUS_CONFIRMED => ['confirmed_at' => $now],
                Appointment::STATUS_COMPLETED => ['completed_at' => $now],
                Appointment::STATUS_CANCELLED => ['cancelled_at' => $now],
                Appointment::STATUS_NO_SHOW => ['no_show_at' => $now],
                default => [],
            };

            $this->appointments->update(
                $appointment,
                ['status' => $status, ...$lifecycleTimestamps],
            );

            if ($appointment->queue) {
                $queueStatus = match ($status) {
                    'cancelled', 'no_show' => 'skipped',
                    'completed' => 'completed',
                    'confirmed' => $appointment->queue->status !== 'serving'
                        ? 'waiting'
                        : $appointment->queue->status,
                    default => $appointment->queue->status,
                };

                if ($appointment->queue->status !== $queueStatus) {
                    $appointment->queue->update(['status' => $queueStatus]);
                }
            }

            return $appointment->fresh(['customer', 'staff', 'service', 'queue', 'statusHistory']);
        });
    }
}
