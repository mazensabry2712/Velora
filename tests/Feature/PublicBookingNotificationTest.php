<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\PublicAppointmentConfirmationMail;
use App\Models\Appointment;
use App\Models\StaffWorkingHours;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class PublicBookingNotificationTest extends TenantTestCase
{
    #[Test]
    public function successful_public_booking_queues_customer_confirmation_with_public_tracking_link(): void
    {
        Mail::fake();

        $timezone = $this->staff->timezone ?: config('app.timezone');
        $this->service->update([
            'is_active' => true,
            'is_online_bookable' => true,
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
        ]);

        $date = now($timezone)->addDay()->startOfDay();
        StaffWorkingHours::create([
            'staff_id' => $this->staff->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'is_working' => true,
        ]);

        $response = $this->postJson('/api/appointments', [
            'customer_name' => 'Notification Customer',
            'customer_email' => 'notification@example.com',
            'customer_phone' => '+201000000010',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => $timezone,
        ]);

        $response->assertCreated()->assertJsonPath('success', true);

        $appointment = Appointment::query()->latest('id')->firstOrFail();

        Mail::assertQueued(PublicAppointmentConfirmationMail::class, function (PublicAppointmentConfirmationMail $mail) use ($appointment): bool {
            return $mail->customerName === 'Notification Customer'
                && $mail->queueNumber !== ''
                && $mail->reference === $appointment->public_reference
                && str_contains($mail->trackingUrl, '/queue/status?ref=' . urlencode($appointment->public_reference));
        });
    }
}
