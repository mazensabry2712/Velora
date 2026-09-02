<?php

namespace App\Domain\Booking\DTOs;

use Carbon\Carbon;

/**
 * Input DTO for creating a new booking.
 * Validated before reaching BookingCreationService.
 */
final readonly class CreateBookingData
{
    public function __construct(
        public int $serviceId,
        public int $staffId,
        public Carbon $startsAt,
        public string $timezone,
        public ?int $customerId = null,
        public ?int $resourceId = null,
        public int $attendees = 1,
        public string $source = 'online',
        public ?string $notes = null,
        public ?int $recurringId = null,
    ) {}
}
