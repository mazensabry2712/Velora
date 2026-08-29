<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PublicBookingConfirmationController extends Controller
{
    public function show(Request $request, string $reference): View
    {
        $appointment = Appointment::query()
            ->where('public_reference', strtoupper(trim($reference)))
            ->with(['service', 'newStaff', 'staff', 'queue'])
            ->firstOrFail();

        abort_unless($appointment->source === 'online', 404);

        $staffName = $appointment->newStaff?->full_name
            ?: $appointment->staff?->name
            ?: __('Any available specialist');

        return view('customer.booking-confirmation', [
            'appointment' => $appointment,
            'queue' => $appointment->queue,
            'staffName' => $staffName,
            'customerName' => trim(($appointment->newCustomer?->first_name ?? '') . ' ' . ($appointment->newCustomer?->last_name ?? '')),
            'reference' => $appointment->public_reference,
        ]);
    }
}
