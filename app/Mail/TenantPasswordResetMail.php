<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class TenantPasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $resetUrl,
        public readonly string $mailLocale,
    ) {}

    public function envelope(): Envelope
    {
        app()->setLocale($this->mailLocale);

        return new Envelope(
            subject: __('password_reset.email_subject'),
        );
    }

    public function content(): Content
    {
        app()->setLocale($this->mailLocale);

        return new Content(
            view: 'emails.tenant-password-reset',
        );
    }
}
