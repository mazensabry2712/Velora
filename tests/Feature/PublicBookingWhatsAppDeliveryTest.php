<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Notifications\Contracts\WhatsAppProvider;
use App\Domain\Notifications\Contracts\WhatsAppSendResult;
use App\Infrastructure\Notifications\NullWhatsAppProvider;
use App\Jobs\SendPublicAppointmentConfirmationWhatsApp;
use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class PublicBookingWhatsAppDeliveryTest extends TenantTestCase
{
    #[Test]
    public function unconfigured_whatsapp_provider_marks_delivery_skipped_instead_of_sent(): void
    {
        $delivery = NotificationDelivery::create([
            'appointment_id' => null,
            'public_reference' => 'VL-WA-SKIP01',
            'event' => 'appointment.booked',
            'channel' => 'whatsapp',
            'recipient' => '+201000000000',
            'provider' => NullWhatsAppProvider::class,
            'status' => 'queued',
            'attempts' => 0,
            'queued_at' => now(),
        ]);

        Config::set('services.whatsapp.enabled', true);
        $this->app->instance(WhatsAppProvider::class, new NullWhatsAppProvider());

        (new SendPublicAppointmentConfirmationWhatsApp(
            tenant: $this->tenant,
            deliveryId: $delivery->id,
            data: [
                'tenant_name' => 'Test Clinic',
                'customer_name' => 'Test Customer',
                'service_name' => 'Consultation',
                'staff_name' => 'Staff Member',
                'appointment_date' => '2026-09-01',
                'appointment_time' => '09:00',
                'duration' => '30',
                'queue_number' => 'A-001',
                'reference' => 'VL-WA-SKIP01',
                'tracking_url' => 'http://velora.test/queue/status?ref=VL-WA-SKIP01',
                'locale' => 'en',
                'recipient' => '+201000000000',
            ],
        ))->handle($this->app->make(WhatsAppProvider::class));

        $this->assertDatabaseHas('notification_deliveries', [
            'id' => $delivery->id,
            'status' => 'skipped',
            'attempts' => 1,
        ]);
    }

    #[Test]
    public function successful_provider_marks_whatsapp_delivery_sent_with_provider_message_id(): void
    {
        $delivery = NotificationDelivery::create([
            'public_reference' => 'VL-WA-SENT01',
            'event' => 'appointment.booked',
            'channel' => 'whatsapp',
            'recipient' => '+201000000000',
            'provider' => 'fake',
            'status' => 'queued',
            'attempts' => 0,
            'queued_at' => now(),
        ]);

        $provider = new class implements WhatsAppProvider {
            public function send(string $recipient, string $message, array $payload = []): WhatsAppSendResult
            {
                return WhatsAppSendResult::sent('wamid.TEST-001');
            }
        };

        (new SendPublicAppointmentConfirmationWhatsApp(
            tenant: $this->tenant,
            deliveryId: $delivery->id,
            data: [
                'tenant_name' => 'Test Clinic',
                'customer_name' => 'Test Customer',
                'service_name' => 'Consultation',
                'staff_name' => 'Staff Member',
                'appointment_date' => '2026-09-01',
                'appointment_time' => '09:00',
                'duration' => '30',
                'queue_number' => 'A-001',
                'reference' => 'VL-WA-SENT01',
                'tracking_url' => 'http://velora.test/queue/status?ref=VL-WA-SENT01',
                'locale' => 'en',
                'recipient' => '+201000000000',
            ],
        ))->handle($provider);

        $delivery->refresh();

        $this->assertSame('sent', $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNotNull($delivery->sent_at);
        $this->assertSame('wamid.TEST-001', $delivery->metadata['provider_message_id'] ?? null);
    }
}
