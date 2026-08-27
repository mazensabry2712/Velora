<?php

declare(strict_types=1);

namespace App\Application\Booking\DTOs;

final readonly class PublicBookingData
{
    public function __construct(
        public string $customerName,
        public string $customerEmail,
        public string $customerPhone,
        public int $serviceId,
        public int $staffUserId,
        public ?int $resourceId,
        public string $appointmentDate,
        public string $appointmentTime,
        public ?string $requestedTimezone,
        public ?string $notes,
    ) {}
}
