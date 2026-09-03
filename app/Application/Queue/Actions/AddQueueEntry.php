<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Domain\Queue\Contracts\QueueRepository;
use App\Models\Appointment;
use App\Models\Queue;
use Illuminate\Validation\ValidationException;

final class AddQueueEntry
{
    public function __construct(
        private readonly QueueRepository $queues,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(int $appointmentId): Queue
    {
        return $this->transactions->transaction(function () use ($appointmentId): Queue {
            $appointment = Appointment::with(['customer', 'queue'])->find($appointmentId);

            if (! $appointment) {
                throw ValidationException::withMessages(['appointment_id' => ['Appointment not found.']]);
            }

            if ($appointment->queue) {
                throw ValidationException::withMessages(['appointment_id' => ['Appointment already in queue.']]);
            }

            return $this->queues->create([
                'appointment_id' => $appointment->id,
                'queue_number' => Queue::generateQueueNumber(),
                'status' => 'waiting',
                'is_vip' => (bool) ($appointment->customer?->ltv_tier === 'vip'),
            ]);
        });
    }
}
