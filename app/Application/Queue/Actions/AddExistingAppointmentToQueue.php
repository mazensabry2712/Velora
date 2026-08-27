<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Models\Queue;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use App\Repositories\Contracts\QueueRepositoryInterface;
use Illuminate\Validation\ValidationException;

final class AddExistingAppointmentToQueue
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly QueueRepositoryInterface $queues,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(int $appointmentId): Queue
    {
        return $this->transactions->transaction(function () use ($appointmentId): Queue {
            $appointment = $this->appointments->findWithRelations($appointmentId, ['customer', 'queue']);

            if (! $appointment) {
                throw ValidationException::withMessages([
                    'appointment_id' => ['Appointment not found.'],
                ]);
            }

            if ($appointment->queue) {
                throw ValidationException::withMessages([
                    'queue' => ['Appointment already in queue.'],
                ]);
            }

            return $this->queues->create([
                'appointment_id' => $appointment->id,
                'queue_number' => Queue::generateQueueNumber(),
                'status' => 'waiting',
                'is_vip' => (bool) ($appointment->customer?->is_vip ?? false),
            ]);
        });
    }
}
