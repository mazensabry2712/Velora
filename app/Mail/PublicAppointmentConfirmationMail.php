<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PublicAppointmentConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $tenantName,
        public readonly string $customerName,
        public readonly string $serviceName,
        public readonly string $staffName,
        public readonly string $appointmentDate,
        public readonly string $appointmentTime,
        public readonly string $duration,
        public readonly string $queueNumber,
        public readonly string $reference,
        public readonly string $trackingUrl,
        public readonly string $locale = 'en',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('notifications.public_booking_confirmation.subject', [], $this->locale),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.public-appointment-confirmation',
            with: [
                'tenantName' => $this->tenantName,
                'customerName' => $this->customerName,
                'serviceName' => $this->serviceName,
                'staffName' => $this->staffName,
                'appointmentDate' => $this->appointmentDate,
                'appointmentTime' => $this->appointmentTime,
                'duration' => $this->duration,
                'queueNumber' => $this->queueNumber,
                'reference' => $this->reference,
                'trackingUrl' => $this->trackingUrl,
                'locale' => $this->locale,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
