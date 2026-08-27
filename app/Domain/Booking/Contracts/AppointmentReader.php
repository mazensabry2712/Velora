<?php

declare(strict_types=1);

namespace App\Domain\Booking\Contracts;

use App\Models\Appointment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AppointmentReader
{
    public function find(int $id, array $relations = []): ?Appointment;

    public function forCustomer(int $customerId): iterable;
}
