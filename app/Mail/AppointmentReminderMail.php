<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly User|Customer $customer,
        public readonly string $locale = 'en',
    ) {
        $this->appointment->loadMissing([
            'service',
            'newStaff',
            'staff',
            'queue',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('notifications.appointment_reminder.subject', [], $this->locale),
        );
    }

    public function content(): Content
    {
        $customerName = $this->customer instanceof Customer
            ? $this->customer->full_name
            : $this->customer->name;

        $serviceName = $this->appointment->service_name
            ?? $this->appointment->service?->name
            ?? $this->appointment->service_type
            ?? 'Appointment';

        $staffName = $this->appointment->newStaff?->full_name
            ?? $this->appointment->staff?->name
            ?? '—';

        $appointmentDate = $this->appointment->starts_at?->format('Y-m-d')
            ?? $this->appointment->date?->format('Y-m-d')
            ?? '';

        $appointmentTime = $this->appointment->starts_at?->format('H:i')
            ?? $this->appointment->time_slot
            ?? '';

        $duration = $this->appointment->service?->duration_minutes
            ?? $this->appointment->service?->duration
            ?? '';

        $queueNumber = $this->appointment->queue?->queue_number;
        $reference = (string) ($this->appointment->public_reference ?? '');
        $trackingUrl = $reference !== ''
            ? route('customer.queue.status', ['ref' => $reference])
            : route('customer.queue.status');

        return new Content(
            markdown: 'emails.appointment-reminder',
            with: [
                'appointment' => $this->appointment,
                'customerName' => $customerName,
                'serviceName' => $serviceName,
                'staffName' => $staffName,
                'appointmentDate' => $appointmentDate,
                'appointmentTime' => $appointmentTime,
                'duration' => (string) $duration,
                'queueNumber' => $queueNumber !== null ? (string) $queueNumber : '—',
                'reference' => $reference,
                'trackingUrl' => $trackingUrl,
                'locale' => $this->locale,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
