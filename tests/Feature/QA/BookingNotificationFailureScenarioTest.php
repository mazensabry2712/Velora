<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Domain\Notifications\Contracts\WhatsAppProvider;
use App\Domain\Notifications\Contracts\WhatsAppSendResult;
use App\Jobs\SendPublicAppointmentConfirmationEmail;
use App\Jobs\SendPublicAppointmentConfirmationWhatsApp;
use App\Models\Appointment;
use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class BookingNotificationFailureScenarioTest extends TenantTestCase
{
    #[Test]
    public function email_failure_is_isolated_from_the_persisted_booking_core(): void
    {
        Mail::fake();

        $appointment = $this->makeAppointment('10:00');
        $delivery = $this->makeDelivery($appointment, 'email', 'failure@example.com', 'mail');

        $job = new SendPublicAppointmentConfirmationEmail(
            tenant: $this->tenant,
            deliveryId: $delivery->id,
            data: $this->notificationData($appointment) + ['recipient' => 'failure@example.com'],
        );

        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('MAIL_OUTAGE'));

        try {
            $job->handle();
            $this->fail('Expected mail provider failure to propagate from the notification job.');
        } catch (RuntimeException $exception) {
            $this->assertSame('MAIL_OUTAGE', $exception->getMessage());
        }

        $freshAppointment = $appointment->fresh();
        $freshDelivery = $delivery->fresh();

        $this->assertNotNull($freshAppointment);
        $this->assertSame(Appointment::STATUS_PENDING, $freshAppointment->status);
        $this->assertSame($this->customerProfile->id, $freshAppointment->customer_id_new);
        $this->assertSame($this->staff->id, $freshAppointment->staff_id_new);
        $this->assertSame('queued', $freshDelivery->status);
        $this->assertSame(1, $freshDelivery->attempts);
        $this->assertStringContainsString('MAIL_OUTAGE', (string) $freshDelivery->last_error);
        $this->assertNull($freshDelivery->sent_at);
    }

    #[Test]
    public function whatsapp_failure_is_requeued_without_rolling_back_the_booking_core(): void
    {
        $appointment = $this->makeAppointment('11:00');
        $delivery = $this->makeDelivery($appointment, 'whatsapp', '+201000000099', 'test-provider');

        $provider = new class implements WhatsAppProvider {
            public function send(string $recipient, string $message, array $payload = []): WhatsAppSendResult
            {
                throw new RuntimeException('WHATSAPP_OUTAGE');
            }
        };

        $job = new SendPublicAppointmentConfirmationWhatsApp(
            tenant: $this->tenant,
            deliveryId: $delivery->id,
            data: $this->notificationData($appointment) + ['recipient' => '+201000000099'],
        );

        try {
            $job->handle($provider);
            $this->fail('Expected WhatsApp provider failure to propagate from the notification job.');
        } catch (RuntimeException $exception) {
            $this->assertSame('WHATSAPP_OUTAGE', $exception->getMessage());
        }

        $freshAppointment = $appointment->fresh();
        $freshDelivery = $delivery->fresh();

        $this->assertNotNull($freshAppointment);
        $this->assertSame(Appointment::STATUS_PENDING, $freshAppointment->status);
        $this->assertSame($this->customerProfile->id, $freshAppointment->customer_id_new);
        $this->assertSame($this->staff->id, $freshAppointment->staff_id_new);
        $this->assertSame('queued', $freshDelivery->status);
        $this->assertSame(1, $freshDelivery->attempts);
        $this->assertStringContainsString('WHATSAPP_OUTAGE', (string) $freshDelivery->last_error);
        $this->assertNull($freshDelivery->sent_at);
    }

    private function makeAppointment(string $time): Appointment
    {
        $startsAt = now()->addDays(2)->setTimeFromTimeString($time);

        return Appointment::create([
            'customer_id_new' => $this->customerProfile->id,
            'staff_id_new' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => $startsAt->toDateString(),
            'time_slot' => $time,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'ends_at_with_buffer' => $startsAt->copy()->addMinutes(30),
            'timezone' => $this->staff->timezone ?: config('app.timezone'),
            'price' => $this->service->price,
            'status' => Appointment::STATUS_PENDING,
            'source' => 'qa-booking-notification-failure',
        ]);
    }

    private function makeDelivery(Appointment $appointment, string $channel, string $recipient, string $provider): NotificationDelivery
    {
        return NotificationDelivery::create([
            'appointment_id' => $appointment->id,
            'public_reference' => $appointment->public_reference,
            'event' => 'appointment.booked',
            'channel' => $channel,
            'recipient' => $recipient,
            'provider' => $provider,
            'status' => 'queued',
            'attempts' => 0,
            'dedupe_key' => sprintf('qa-booking-failure|%s|%s', $channel, $appointment->id),
            'queued_at' => now(),
        ]);
    }

    /** @return array<string, string> */
    private function notificationData(Appointment $appointment): array
    {
        return [
            'tenant_name' => $this->tenant->name,
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
        ];
    }
}
