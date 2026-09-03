<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Models\Appointment;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class UpdateAdminAppointment
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly \App\Application\Shared\Contracts\TransactionManager $transactions,
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
            $newTime = $data['appointment_time'] ?? $appointment->time_slot;
            $dateChanged = $appointment->date->format('Y-m-d') !== $newDate;
            $timeChanged = $appointment->time_slot !== $newTime;

            if (($dateChanged || $timeChanged) && $appointment->queue && \Carbon\Carbon::parse($newDate)->lt(today())) {
                $appointment->queue->delete();
            }

            if ($appointment->customer) {
                $name = preg_split('/\s+/', trim((string) ($data['customer_name'] ?? $appointment->customer->full_name)), 2) ?: [];
                $appointment->customer->update([
                    'first_name' => $name[0] ?? $appointment->customer->first_name,
                    'last_name' => $name[1] ?? $appointment->customer->last_name,
                    'phone' => $data['customer_phone'] ?? $appointment->customer->phone,
                    'email' => $data['customer_email'] ?? $appointment->customer->email,
                ]);
            }

            if (array_key_exists('status', $data)) {
                $appointment = $this->changeStatus->execute($appointmentId, (string) $data['status']);
            }

            $serviceChanged = array_key_exists('service_id', $data) && $data['service_id'] !== $appointment->service_id;
            $staffChanged = array_key_exists('staff_id', $data) && $data['staff_id'] !== $appointment->staff_id_new;

            $appointmentData = [
                'staff_id_new' => $data['staff_id'] ?? $appointment->staff_id_new,
                'service_id' => $data['service_id'] ?? $appointment->service_id,
                'date' => $newDate,
                'time_slot' => $newTime,
                'starts_at' => $newDate . ' ' . $newTime,
                'service_type' => $data['service_type'] ?? $appointment->service_type,
                'notes' => $data['notes'] ?? $appointment->notes,
            ];

            if ($serviceChanged || $staffChanged || $dateChanged || $timeChanged) {
                $appointmentData['status'] = array_key_exists('status', $data)
                    ? $appointment->status
                    : Appointment::STATUS_PENDING;
            }

            $this->appointments->update($appointment, $appointmentData);

            return $appointment->fresh(['customer', 'staff', 'service', 'queue']);
        });
    }
}
