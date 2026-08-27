<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Models\Appointment;
use App\Repositories\Contracts\AppointmentRepositoryInterface;

final class CompleteBulkAppointmentStatusUpdate
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly TransactionManager $transactions,
    ) {}

    /** @param list<int> $appointmentIds */
    public function execute(array $appointmentIds, string $action): int
    {
        return $this->transactions->transaction(function () use ($appointmentIds, $action): int {
            $query = Appointment::query()->whereIn('id', $appointmentIds);

            return match ($action) {
                'confirm_all' => $query->where('status', 'pending')->update(['status' => 'confirmed']),
                'complete_all' => $query->whereIn('status', ['confirmed', 'pending'])->update(['status' => 'completed']),
                default => 0,
            };
        });
    }
}
