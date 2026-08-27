<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Application\Shared\Contracts\TransactionManager;
use App\Models\Appointment;
use App\Models\BusinessRule;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use App\Repositories\Contracts\QueueRepositoryInterface;
use Illuminate\Support\Str;
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
            $email = $data['customer_email'] ?? ((string) $data['customer_phone']) . '@temp.local';

            $customer = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $data['customer_name'],
                    'phone' => $data['customer_phone'],
                    'password' => bcrypt(Str::random(32)),
                ],
            );

            if (! $customer->hasRole('Customer')) {
                $customer->assignRole('Customer');
            }

            $customer->update([
                'name' => $data['customer_name'],
                'phone' => $data['customer_phone'],
            ]);

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

            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'staff_id' => $data['staff_id'],
                'service_id' => $data['service_id'],
                'date' => now()->toDateString(),
                'time_slot' => now()->format('H:i'),
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
