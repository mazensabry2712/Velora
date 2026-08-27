<?php

declare(strict_types=1);

namespace App\Domain\Booking\Contracts;

use App\Models\Appointment;

interface AppointmentCommand
{
    /** @param array<string, mixed> $data */
    public function create(array $data): Appointment;

    /** @param array<string, mixed> $data */
    public function update(Appointment $appointment, array $data): bool;

    public function delete(Appointment $appointment): bool;
}
