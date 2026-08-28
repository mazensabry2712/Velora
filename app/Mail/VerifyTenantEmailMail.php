<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class VerifyTenantEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $businessName,
        public readonly string $tenantId,
        public readonly string $domain,
        public readonly string $verificationUrl,
        public readonly int $expiresInHours = 24,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your Velora email address',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-verify-email',
        );
    }
}
