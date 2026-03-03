<?php

namespace App\Domain\Booking\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly string      $fromStatus,
        public readonly string      $toStatus,
        public readonly ?int        $actorId   = null,
        public readonly string      $actorType = 'system',
        public readonly ?string     $reason    = null,
    ) {}
}
