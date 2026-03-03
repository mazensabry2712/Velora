<?php

namespace App\Domain\Booking\DTOs;

/**
 * Result of a slot validation check.
 */
final readonly class SlotValidationResult
{
    private function __construct(
        public bool   $available,
        public string $reason = '',
    ) {}

    public static function available(): self
    {
        return new self(true);
    }

    public static function unavailable(string $reason): self
    {
        return new self(false, $reason);
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
