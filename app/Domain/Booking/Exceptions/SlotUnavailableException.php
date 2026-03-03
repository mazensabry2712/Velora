<?php

namespace App\Domain\Booking\Exceptions;

use RuntimeException;

class SlotUnavailableException extends RuntimeException
{
    public function __construct(
        private readonly string $reason = 'slot_not_available',
        string $message = '',
    ) {
        parent::__construct($message ?: "Slot unavailable: {$reason}");
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
