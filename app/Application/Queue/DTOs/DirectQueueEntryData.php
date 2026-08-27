<?php

declare(strict_types=1);

namespace App\Application\Queue\DTOs;

final readonly class DirectQueueEntryData
{
    public function __construct(
        public string $customerName,
        public string $customerPhone,
        public ?string $customerEmail,
        public int $staffId,
        public int $serviceId,
        public bool $isPriority,
        public ?string $notes,
    ) {}
}
