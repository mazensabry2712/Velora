<?php

declare(strict_types=1);

namespace App\Application\Queue\Actions;

use App\Application\Queue\DTOs\DirectQueueEntryData;
use App\Application\Shared\Contracts\TransactionManager;
use App\Models\Appointment;
use App\Models\BusinessRule;
use App\Models\Queue;
use App\Models\Service;
use App\Models\User;
use App\Repositories\Contracts\QueueRepositoryInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

final class AddDirectQueueEntry
{
    public function __construct(
        private readonly QueueRepositoryInterface $queues,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(DirectQueueEntryData $data): Queue
    {
        return $this->transactions->transaction(function () use ($data): Queue {
            $this->assertCapacity();

            $email = $data->customerEmail ?: $data->customerPhone . '@temp.local';

            $customer = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $data->customerName,
                    'phone' => $data->customerPhone,
                    'password' => bcrypt(Str::random(32)),
                ],
            );

            if (! $customer->hasRole('Customer')) {
                $customer->assignRole('Customer');
            }

            $customer->update([
                'name' => $data->customerName,
                'phone' => $data->customerPhone,
            ]);

            $service = Service::find($data->serviceId);

            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'staff_id' => $data->staffId,
                'service_id' => $data->serviceId,
                'date' => now()->toDateString(),
                'time_slot' => now()->format('H:i'),
                'status' => 'pending',
                'service_type' => $service?->name,
            ]);

            return $this->queues->create([
                'appointment_id' => $appointment->id,
                'queue_number' => Queue::generateQueueNumber(),
                'status' => 'waiting',
                'is_vip' => $data->isPriority,
                'notes' => $data->notes,
            ]);
        });
    }

    private function assertCapacity(): void
    {
        $maxSize = (int) BusinessRule::getValue(BusinessRule::QUEUE_MAX_SIZE, 0);

        if ($maxSize <= 0) {
            return;
        }

        $currentSize = Queue::whereDate('created_at', today())
            ->whereIn('status', ['waiting', 'serving'])
            ->lockForUpdate()
            ->count();

        if ($currentSize >= $maxSize) {
            throw ValidationException::withMessages([
                'queue' => [__('Queue is full. Maximum size of :max has been reached.', ['max' => $maxSize])],
            ]);
        }
    }
}
