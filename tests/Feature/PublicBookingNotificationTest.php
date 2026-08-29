<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendPublicAppointmentConfirmationEmail;
use App\Models\Appointment;
use App\Models\NotificationDelivery;
use App\Models\StaffWorkingHours;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class PublicBookingNotificationTest extends TenantTestCase
{
    #[Test]
    public function successful_public_booking_creates_one_queued_email_delivery(): void
    {
        Queue::fake();
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
        $delivery = NotificationDelivery::query()->where('public_reference', $appointment->public_reference)->firstOrFail();

        Queue::assertPushed(SendPublicAppointmentConfirmationEmail::class, function (SendPublicAppointmentConfirmationEmail $job) use ($delivery, $appointment): bool {
            return $job->deliveryId === $delivery->id
                && $job->data['reference'] === $appointment->public_reference
                && str_contains($job->data['tracking_url'], '/queue/status?ref=' . urlencode($appointment->public_reference));
        });

        self::assertSame('appointment.booked', $delivery->event);
        self::assertSame('email', $delivery->channel);
        self::assertSame('queued', $delivery->status);
        self::assertSame(0, $delivery->attempts);
    }

    #[Test]
    public function email_job_marks_delivery_sent_after_successful_send(): void
    {
        Mail::fake();

        $delivery = NotificationDelivery::create([
            'event' => 'appointment.booked',
            'channel' => 'email',
            'recipient' => 'customer@example.com',
            'provider' => 'mail',
            'status' => 'queued',
            'attempts' => 0,
            'dedupe_key' => 'appointment.booked|email|VL-TEST001',
            'queued_at' => now(),
            'public_reference' => 'VL-TEST001',
        ]);

        $job = new SendPublicAppointmentConfirmationEmail(
            tenant: tenant(),
            deliveryId: (int) $delivery->id,
            data: [
                'tenant_name' => 'Test Clinic',
                'customer_name' => 'Test Customer',
                'service_name' => 'Consultation',
                'staff_name' => 'Staff Member',
                'appointment_date' => '2026-09-01',
                'appointment_time' => '09:00',
                'duration' => '30',
                'queue_number' => 'A-001',
                'reference' => 'VL-TEST001',
                'tracking_url' => 'http://velora.test/queue/status?ref=VL-TEST001',
                'locale' => 'en',
                'recipient' => 'customer@example.com',
            ],
        );

        $job->handle();

        $delivery->refresh();
        self::assertSame('sent', $delivery->status);
        self::assertSame(1, $delivery->attempts);
        self::assertNotNull($delivery->sent_at);
        Mail::assertSent(\App\Mail\PublicAppointmentConfirmationMail::class);
    }
}
