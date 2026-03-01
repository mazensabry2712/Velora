<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeTenantMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $businessName,
        public readonly string $subdomain,
        public readonly string $fullDomain,
        public readonly int    $trialDays = 14,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Welcome to Velora! Your {$this->trialDays}-day trial has started 🎉",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-tenant',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
