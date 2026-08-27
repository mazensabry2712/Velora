<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Application\Booking\DTOs\PublicBookingData;
use App\Application\Shared\Contracts\TransactionManager;
use App\Domain\Booking\DTOs\CreateBookingData;
use App\Domain\Booking\Services\BookingCreationService;
use App\Models\Customer;
use App\Models\Queue;
use App\Models\Resource;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

final class CreatePublicBooking
{
    public function __construct(
        private readonly BookingCreationService $bookingService,
        private readonly TransactionManager $transactions,
    ) {}

    /** @return array{appointment: mixed, queue: Queue, customer: Customer} */
    public function execute(PublicBookingData $data): array
    {
        $staff = Staff::query()
            ->where('user_id', $data->staffUserId)
            ->where('is_active', true)
            ->where('accepts_bookings', true)
            ->first();

        if (! $staff) {
            $this->fail('staff_id', 'The selected staff member is not available for booking.');
        }

        if (! $staff->services()->where('services.id', $data->serviceId)->exists()) {
            $this->fail('staff_id', 'The selected staff member cannot provide this service.');
        }

        if ($data->resourceId !== null) {
            $resourceValid = Resource::query()
                ->whereKey($data->resourceId)
                ->where('is_active', true)
                ->whereHas('services', fn ($query) => $query->whereKey($data->serviceId))
                ->exists();

            if (! $resourceValid) {
                $this->fail('resource_id', 'The selected resource is not available for this service.');
            }
        }

        $timezone = $staff->timezone ?: config('app.timezone');
        $startsAt = Carbon::createFromFormat(
            'Y-m-d H:i',
            $data->appointmentDate . ' ' . $data->appointmentTime,
            $timezone,
        );

        return $this->transactions->transaction(function () use ($data, $staff, $timezone, $startsAt): array {
            [$firstName, $lastName] = $this->splitName($data->customerName);

            $customer = Customer::firstOrNew(['email' => $data->customerEmail]);

            if ($customer->exists && $customer->is_blocked) {
                $this->fail('customer_email', 'This customer is not allowed to book appointments.');
            }

            $customer->fill([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $data->customerPhone,
                'acquisition_source' => $customer->exists ? $customer->acquisition_source : 'online',
            ]);
            $customer->save();

            $appointment = $this->bookingService->create(new CreateBookingData(
                serviceId: $data->serviceId,
                staffId: $staff->id,
                startsAt: $startsAt,
                timezone: $timezone,
                customerId: $customer->id,
                resourceId: $data->resourceId,
                source: 'online',
                notes: $data->notes,
            ));

            $queue = Queue::create([
                'appointment_id' => $appointment->id,
                'queue_number' => Queue::generateQueueNumber($startsAt),
                'queue_date' => $startsAt->toDateString(),
                'status' => 'waiting',
                'is_vip' => false,
                'notes' => $data->notes,
            ]);

            return [
                'appointment' => $appointment,
                'queue' => $queue,
                'customer' => $customer,
            ];
        });
    }

    private function splitName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    private function fail(string $field, string $message): never
    {
        throw Validator::make([], [])->after(function ($validator) use ($field, $message): void {
            $validator->errors()->add($field, $message);
        })->validate();
    }
}
