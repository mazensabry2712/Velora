<?php

declare(strict_types=1);

namespace App\Application\Booking\Actions;

use App\Domain\Booking\DTOs\CreateBookingData;
use App\Domain\Booking\Services\BookingCreationService;
use App\Models\Appointment;

final class CreateBooking
{
    public function __construct(
        private readonly BookingCreationService $bookingCreation,
    ) {}

    public function execute(CreateBookingData $data): Appointment
    {
        return $this->bookingCreation->create($data);
    }
}
