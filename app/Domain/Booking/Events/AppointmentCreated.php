<?php

namespace App\Domain\Booking\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
    ) {}
}
