<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Application\Booking\DTOs\CreateAdminAppointmentData;
use App\Application\Shared\Contracts\TransactionManager;
use App\Models\Appointment;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use App\Models\UsageLog;
use App\Repositories\Contracts\AppointmentRepositoryInterface;

final class CreateAdminAppointment
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly TransactionManager $transactions,
    ) {}

    /** @return array{appointment: Appointment, customer: User, queue: ?Queue} */
    public function execute(CreateAdminAppointmentData $data): array
    {
        return $this->transactions->transaction(function () use ($data): array {
            $customer = $this->findOrCreateCustomer($data);

            $appointment = $this->appointments->create([
                'customer_id' => $customer->id,
                'staff_id' => $data->staffId,
                'service_id' => $data->serviceId,
                'date' => $data->appointmentDate,
                'time_slot' => $data->appointmentTime,
                'status' => $data->addToQueue ? 'confirmed' : 'pending',
                'service_type' => $data->serviceType,
                'notes' => $data->notes,
            ]);

            try {
                UsageLog::log('appointment_created', [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $customer->id,
                    'service_id' => $appointment->service_id,
                    'date' => $appointment->date,
                ]);
            } catch (\Throwable) {
                // Telemetry failure must not roll back a successful appointment.
            }

            $queue = null;
            if ($data->addToQueue) {
                $queue = Queue::create([
                    'appointment_id' => $appointment->id,
                    'queue_number' => Queue::generateQueueNumber(),
                    'queue_date' => $data->queueDate ?? $data->appointmentDate,
                    'status' => 'waiting',
                    'is_vip' => $customer->is_vip ?? false,
                ]);
            }

            return [
                'appointment' => $appointment->load(['customer', 'staff', 'service', 'queue']),
                'customer' => $customer,
                'queue' => $queue,
            ];
        });
    }

    private function findOrCreateCustomer(CreateAdminAppointmentData $data): User
    {
        $email = $data->customerEmail ?: $data->customerPhone . '@temp.local';

        $customer = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $data->customerName,
                'phone' => $data->customerPhone,
                'password' => bcrypt(bin2hex(random_bytes(16))),
            ],
        );

        if (! $customer->hasRole('Customer')) {
            $customer->assignRole('Customer');
        }

        $customer->update([
            'name' => $data->customerName,
            'phone' => $data->customerPhone,
            'email' => $data->customerEmail ?: $customer->email,
        ]);

        return $customer;
    }
}
