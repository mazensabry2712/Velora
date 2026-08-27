<?php

declare(strict_types=1);

namespace App\Domain\Booking\Rules;

use DomainException;

final class AppointmentStatusTransition
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['checked_in', 'in_service', 'completed', 'cancelled', 'no_show'],
        'checked_in' => ['in_service', 'completed', 'cancelled', 'no_show'],
        'in_service' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
        'no_show' => [],
    ];

    public function assertAllowed(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        if (! in_array($to, self::ALLOWED[$from] ?? [], true)) {
            throw new DomainException("Invalid appointment status transition: {$from} -> {$to}");
        }
    }
}
