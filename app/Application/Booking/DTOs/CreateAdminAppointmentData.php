<?php

declare(strict_types=1);

namespace App\Application\Booking\DTOs;

final readonly class CreateAdminAppointmentData
{
    public function __construct(
        public string $customerName,
        public string $customerPhone,
        public ?string $customerEmail,
        public ?int $staffId,
        public ?int $serviceId,
        public string $appointmentDate,
        public string $appointmentTime,
        public ?string $serviceType,
        public ?string $notes,
        public bool $addToQueue,
        public ?string $queueDate,
    ) {}
}
