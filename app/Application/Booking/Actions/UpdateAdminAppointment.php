<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Models\Appointment;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class UpdateAdminAppointment
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly TransactionManager $transactions,
        private readonly ChangeAppointmentStatus $changeStatus,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(int $appointmentId, array $data): Appointment
    {
        return $this->transactions->transaction(function () use ($appointmentId, $data): Appointment {
            $appointment = $this->appointments->findWithRelations($appointmentId, ['customer', 'queue']);

            if (! $appointment) {
                throw (new ModelNotFoundException())->setModel(Appointment::class, [$appointmentId]);
            }

            $newDate = $data['appointment_date'] ?? $appointment->date->format('Y-m-d');
            $dateChanged = $appointment->date->format('Y-m-d') !== $newDate;
            $timeChanged = isset($data['appointment_time']) && $appointment->time_slot !== $data['appointment_time'];

            if (($dateChanged || $timeChanged) && $appointment->queue && \Carbon\Carbon::parse($newDate)->lt(today())) {
                $appointment->queue->delete();
            }

            if ($appointment->customer) {
                $appointment->customer->update([
                    'name' => $data['customer_name'],
                    'phone' => $data['customer_phone'],
                    'email' => $data['customer_email'] ?? $appointment->customer->email,
                ]);
            }

            $appointmentData = [
                'staff_id' => $data['staff_id'] ?? $appointment->staff_id,
                'service_id' => $data['service_id'] ?? $appointment->service_id,
                'date' => $newDate,
                'time_slot' => $data['appointment_time'] ?? $appointment->time_slot,
                'service_type' => $data['service_type'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            if (array_key_exists('status', $data)) {
                $appointment = $this->changeStatus->execute($appointmentId, (string) $data['status']);
            }

            $this->appointments->update($appointment, $appointmentData);

            return $appointment->fresh(['customer', 'staff', 'service', 'queue']);
        });
    }
}
