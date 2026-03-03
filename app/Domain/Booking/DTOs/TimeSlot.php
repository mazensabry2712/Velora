<?php

namespace App\Domain\Booking\DTOs;

use Carbon\Carbon;

/**
 * Represents a single available booking slot.
 */
final readonly class TimeSlot
{
    public function __construct(
        public Carbon $startsAt,
        public Carbon $endsAt,
        public Carbon $endsAtWithBuffer,
        public bool   $isAvailable = true,
    ) {}

    public function toArray(): array
    {
        return [
            'starts_at'             => $this->startsAt->toIso8601String(),
            'ends_at'               => $this->endsAt->toIso8601String(),
            'ends_at_with_buffer'   => $this->endsAtWithBuffer->toIso8601String(),
            'time'                  => $this->startsAt->format('H:i'),
            'is_available'          => $this->isAvailable,
        ];
    }
}
