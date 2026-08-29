<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class QueueLifecycleNotificationMail extends Mailable
{
    public function __construct(
        public readonly string $customerName,
        public readonly string $updateType,
        public readonly string $queueNumber,
        public readonly ?int $position,
        public readonly string $mailLocale = 'en',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('notifications.queue_' . $this->updateType . '.subject', [], $this->mailLocale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.queue-lifecycle-update',
            with: [
                'customerName' => $this->customerName,
                'updateType' => $this->updateType,
                'queueNumber' => $this->queueNumber,
                'position' => $this->position,
                'locale' => $this->mailLocale,
                'direction' => $this->mailLocale === 'ar' ? 'rtl' : 'ltr',
                'tenantName' => tenant()?->name ?? config('app.name', 'Velora'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
