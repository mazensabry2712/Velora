<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Domain\Queue\Contracts\QueueRepository;
use App\Models\Appointment;
use App\Models\Queue;
use App\Repositories\Contracts\AppointmentRepositoryInterface;

final class AdvanceQueue
{
    public function __construct(
        private readonly QueueRepository $queues,
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(): ?Queue
    {
        return $this->transactions->transaction(function (): ?Queue {
            $today = now()->toDateString();
            $queues = $this->queues->getByDate($today);

            foreach ($queues->where('status', 'serving') as $current) {
                $this->queues->update($current, ['status' => 'completed']);
            }

            $next = $queues
                ->where('status', 'waiting')
                ->sortByDesc('is_vip')
                ->sortBy('queue_number')
                ->first();

            if (! $next) {
                return null;
            }

            $this->queues->update($next, ['status' => 'serving']);

            if ($next->appointment) {
                $this->appointments->update($next->appointment, [
                    'status' => Appointment::STATUS_CONFIRMED,
                ]);
            }

            return $next->refresh()->load(['appointment.customer', 'appointment.staff']);
        });
    }
}
