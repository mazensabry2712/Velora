<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Application\Booking\DTOs\CreateAdminAppointmentData;
use App\Application\Shared\Contracts\TransactionManager;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Queue;
use App\Models\UsageLog;
use App\Repositories\Contracts\AppointmentRepositoryInterface;

final class CreateAdminAppointment
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointments,
        private readonly TransactionManager $transactions,
    ) {}

    /** @return array{appointment: Appointment, customer: Customer, queue: ?Queue} */
    public function execute(CreateAdminAppointmentData $data): array
    {
        return $this->transactions->transaction(function () use ($data): array {
            $customer = $this->findOrCreateCustomer($data);

            $appointment = $this->appointments->create([
                'customer_id_new' => $customer->id,
                'staff_id_new' => $data->staffId,
                'service_id' => $data->serviceId,
                'date' => $data->appointmentDate,
                'time_slot' => $data->appointmentTime,
                'starts_at' => $data->appointmentDate . ' ' . $data->appointmentTime,
                'status' => $data->addToQueue ? 'confirmed' : 'pending',
                'service_type' => $data->serviceType,
                'notes' => $data->notes,
                'source' => 'admin',
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
                    'is_vip' => $customer->ltv_tier === 'vip',
                ]);
            }

            return [
                'appointment' => $appointment->load(['customer', 'staff', 'service', 'queue']),
                'customer' => $customer,
                'queue' => $queue,
            ];
        });
    }

    private function findOrCreateCustomer(CreateAdminAppointmentData $data): Customer
    {
        $customer = null;

        if ($data->customerEmail) {
            $customer = Customer::query()->where('email', $data->customerEmail)->first();
        }

        if (! $customer) {
            $customer = Customer::query()->where('phone', $data->customerPhone)->first();
        }

        if (! $customer) {
            $name = preg_split('/\s+/', trim($data->customerName), 2) ?: [];

            $customer = Customer::create([
                'first_name' => $name[0] ?? $data->customerName,
                'last_name' => $name[1] ?? '',
                'email' => $data->customerEmail,
                'phone' => $data->customerPhone,
                'is_blocked' => false,
                'ltv_tier' => 'new',
            ]);
        } else {
            $name = preg_split('/\s+/', trim($data->customerName), 2) ?: [];
            $customer->update([
                'first_name' => $name[0] ?? $customer->first_name,
                'last_name' => $name[1] ?? $customer->last_name,
                'email' => $data->customerEmail ?? $customer->email,
                'phone' => $data->customerPhone,
            ]);
        }

        return $customer;
    }
}
