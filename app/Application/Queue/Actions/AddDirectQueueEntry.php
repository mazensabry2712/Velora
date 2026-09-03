<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Models\Appointment;
use App\Models\BusinessRule;
use App\Models\Customer;
use App\Models\Queue;
use App\Models\Service;
use App\Repositories\Contracts\QueueRepositoryInterface;
use RuntimeException;

final class AddDirectQueueEntry
{
    public function __construct(
        private readonly QueueRepositoryInterface $queues,
        private readonly TransactionManager $transactions,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data): Queue
    {
        return $this->transactions->transaction(function () use ($data): Queue {
            $customer = null;

            if (! empty($data['customer_email'])) {
                $customer = Customer::query()->where('email', $data['customer_email'])->first();
            }

            if (! $customer) {
                $customer = Customer::query()->where('phone', $data['customer_phone'])->first();
            }

            if (! $customer) {
                $name = preg_split('/\s+/', trim((string) $data['customer_name']), 2) ?: [];
                $customer = Customer::create([
                    'first_name' => $name[0] ?? $data['customer_name'],
                    'last_name' => $name[1] ?? '',
                    'email' => $data['customer_email'] ?? null,
                    'phone' => $data['customer_phone'],
                    'is_blocked' => false,
                    'ltv_tier' => 'new',
                ]);
            }

            $maxSize = (int) BusinessRule::getValue(BusinessRule::QUEUE_MAX_SIZE, 0);
            if ($maxSize > 0) {
                $currentSize = Queue::whereDate('created_at', today())
                    ->whereIn('status', ['waiting', 'serving'])
                    ->lockForUpdate()
                    ->count();

                if ($currentSize >= $maxSize) {
                    throw new RuntimeException(
                        __('Queue is full. Maximum size of :max has been reached.', ['max' => $maxSize])
                    );
                }
            }

            $service = Service::find($data['service_id']);
            $now = now();

            $appointment = Appointment::create([
                'customer_id_new' => $customer->id,
                'staff_id_new' => $data['staff_id'],
                'service_id' => $data['service_id'],
                'date' => $now->toDateString(),
                'time_slot' => $now->format('H:i'),
                'starts_at' => $now,
                'status' => 'pending',
                'service_type' => $service?->name,
            ]);

            return $this->queues->create([
                'appointment_id' => $appointment->id,
                'queue_number' => Queue::generateQueueNumber(),
                'status' => 'waiting',
                'is_vip' => $data['is_priority'] ?? false,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }
}
