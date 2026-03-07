<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the tenant when they submit an upgrade request,
 * confirming that their request has been received.
 */
class UpgradeRequestedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $tenantName,
        public readonly string $currentPlanName,
        public readonly string $requestedPlanName,
        public readonly string $requestedPlanPrice,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📋 Upgrade Request Received — We\'ll Review It Shortly',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.upgrade-requested',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
