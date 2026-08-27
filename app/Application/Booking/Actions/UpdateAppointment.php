<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Models\Appointment;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class UpdateAppointment
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly ChangeAppointmentStatus $changeStatus,
    ) {}

    public function execute(int $appointmentId, array $data): Appointment
    {
        $appointment = $this->appointments->findWithRelations($appointmentId, ['queue']);

        if (! $appointment) {
            throw (new ModelNotFoundException())->setModel(Appointment::class, [$appointmentId]);
        }

        if (array_key_exists('status', $data)) {
            $appointment = $this->changeStatus->execute($appointmentId, (string) $data['status']);
            unset($data['status']);
        }

        if ($data !== []) {
            $this->appointments->update($appointment, $data);
        }

        return $appointment->fresh(['customerNew', 'staffNew', 'service', 'queue']);
    }
}
