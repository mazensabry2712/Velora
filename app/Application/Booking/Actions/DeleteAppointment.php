<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Models\Appointment;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class DeleteAppointment
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
    ) {}

    public function execute(int $appointmentId): void
    {
        $appointment = $this->appointments->findById($appointmentId);

        if (! $appointment) {
            throw (new ModelNotFoundException())->setModel(Appointment::class, [$appointmentId]);
        }

        $this->appointments->delete($appointment);
    }
}
