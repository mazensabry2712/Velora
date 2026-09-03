<?php

declare(strict_types=1);

namespace App\Infrastructure\Booking;

use App\Domain\Booking\Contracts\AppointmentReader;
use App\Models\Appointment;

final class EloquentAppointmentReader implements AppointmentReader
{
    public function find(int $id, array $relations = []): ?Appointment
    {
        return Appointment::query()->with($relations)->find($id);
    }

    public function forCustomer(int $customerId): iterable
    {
        return Appointment::query()
            ->where('customer_id_new', $customerId)
            ->with('staff:id,name')
            ->orderByDesc('starts_at')
            ->get();
    }
}
