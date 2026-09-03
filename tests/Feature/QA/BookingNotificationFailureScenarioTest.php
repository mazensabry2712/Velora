<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Domain\Booking\Events\AppointmentCreated;
use App\Jobs\SendPublicAppointmentConfirmationEmail;
use App\Jobs\SendPublicAppointmentConfirmationWhatsApp;
use App\Models\Appointment;
use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class BookingNotificationFailureScenarioTest extends TenantTestCase
{
    #[Test]
    public function email_dispatch_failure_is_isolated_from_the_persisted_booking(): void
    {
        Bus::fake();
        Mail::fake();

        $appointment = Appointment::create([
            'customer_id_new' => $this->customerProfile->id,
            'staff_id_new' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now()->addDay()->toDateString(),
            'time_slot' => '10:00',
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(10, 30),
            'ends_at_with_buffer' => now()->addDay()->setTime(10, 30),
            'timezone' => $this->staff->timezone ?: config('app.timezone'),
            'price' => $this->service->price,
            'status' => Appointment::STATUS_PENDING,
            'source' => 'qa-notification-failure',
        ]);

        $delivery = NotificationDelivery::create([
            'appointment_id' => $appointment->id,
            'public_reference' => $appointment->public_reference,
            'event' => 'appointment.booked',
            'channel' => 'email',
            'recipient' => 'failure@example.com',
            'provider' => 'mail',
            'status' => 'queued',
            'attempts' => 0,
            'dedupe_key' => 'appointment.booked|email|' . $appointment->public_reference,
            'queued_at' => now(),
        ]);

        event(new AppointmentCreated($appointment));

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => Appointment::STATUS_PENDING]);
        $this->assertDatabaseHas('notification_deliveries', ['id' => $delivery->id, 'status' => 'queued']);

        Bus::assertNothingPushed();

        $job = new SendPublicAppointmentConfirmationEmail(
            tenant: tenant(),
            deliveryId: $delivery->id,
            data: [
                'tenant_name' => tenant()->name,
                'customer_name' => $this->customerProfile->full_name,
                'service_name' => $this->service->name,
                'staff_name' => $this->staff->name,
                'appointment_date' => $appointment->starts_at->format('Y-m-d'),
                'appointment_time' => $appointment->starts_at->format('H:i'),
                'duration' => '30',
                'queue_number' => 'A001',
                'reference' => $appointment->public_reference,
                'tracking_url' => '/queue/status/A001',
                'locale' => 'en',
                'recipient' => 'failure@example.com',
            ],
        );

        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('MAIL_OUTAGE'));

        $this->expectException(\RuntimeException::class);
        try {
            $job->handle();
        } finally {
            $freshDelivery = $delivery->fresh();
            $this->assertSame('queued', $freshDelivery->status);
            $this->assertSame(1, $freshDelivery->attempts);
            $this->assertStringContainsString('MAIL_OUTAGE', (string) $freshDelivery->last_error);
            $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
        }
    }

    #[Test]
    public function whatsapp_failure_does_not_rollback_the_booking_core(): void
    {
        $appointment = Appointment::create([
            'customer_id_new' => $this->customerProfile->id,
            'staff_id_new' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now()->addDays(2)->toDateString(),
            'time_slot' => '11:00',
            'starts_at' => now()->addDays(2)->setTime(11, 0),
            'ends_at' => now()->addDays(2)->setTime(11, 30),
            'ends_at_with_buffer' => now()->addDays(2)->setTime(11, 30),
            'timezone' => $this->staff->timezone ?: config('app.timezone'),
            'price' => $this->service->price,
            'status' => Appointment::STATUS_PENDING,
            'source' => 'qa-notification-failure',
        ]);

        $delivery = NotificationDelivery::create([
            'appointment_id' => $appointment->id,
            'public_reference' => $appointment->public_reference,
            'event' => 'appointment.booked',
            'channel' => 'whatsapp',
            'recipient' => '+201000000099',
            'provider' => 'test-provider',
            'status' => 'queued',
            'attempts' => 0,
            'dedupe_key' => 'appointment.booked|whatsapp|' . $appointment->public_reference,
            'queued_at' => now(),
        ]);

        $provider = new class implements \App\Domain\Notifications\Contracts\WhatsAppProvider {
            public function send(string $to, string $message, array $context = []): \App\Domain\Notifications\DTOs\WhatsAppSendResult
            {
                throw new \RuntimeException('WHATSAPP_OUTAGE');
            }
        };

        $job = new SendPublicAppointmentConfirmationWhatsApp(
            tenant: tenant(),
            deliveryId: $delivery->id,
            data: [
                'tenant_name' => tenant()->name,
                'service_name' => $this->service->name,
                'staff_name' => $this->staff->name,
                'appointment_date' => $appointment->starts_at->format('Y-m-d'),
                'appointment_time' => $appointment->starts_at->format('H:i'),
                'queue_number' => 'A002',
                'reference' => $appointment->public_reference,
                'tracking_url' => '/queue/status/A002',
                'recipient' => '+201000000099',
            ],
        );

        $this->expectException(\RuntimeException::class);
        try {
            $job->handle($provider);
        } finally {
            $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => Appointment::STATUS_PENDING]);
            $freshDelivery = $delivery->fresh();
            $this->assertSame('queued', $freshDelivery->status);
            $this->assertSame(1, $freshDelivery->attempts);
            $this->assertStringContainsString('WHATSAPP_OUTAGE', (string) $freshDelivery->last_error);
        }
    }
}
