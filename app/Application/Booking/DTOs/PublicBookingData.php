<?php

declare(strict_types=1);

namespace App\Application\Booking\DTOs;

use Carbon\Carbon;

final readonly class PublicBookingData
{
    public function __construct(
        public string $customerName,
        public string $customerEmail,
        public string $customerPhone,
        public int $serviceId,
        public int $staffUserId,
        public ?int $resourceId,
        public Carbon $startsAt,
        public string $timezone,
        public ?string $notes,
    ) {}
}
