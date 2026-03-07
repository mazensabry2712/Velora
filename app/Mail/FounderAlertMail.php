<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the SuperAdmin (founder) when a high-intent trial trigger fires:
 *  - ≥10 appointments made
 *  - Queue used ≥3 times
 *  - Pricing page visited
 *  - Day 11 without upgrade
 */
class FounderAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $businessName,
        public readonly string $ownerEmail,
        public readonly string $triggerReason,
        public readonly int    $trialDaysLeft,
        public readonly array  $stats = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🔔 Velora Founder Alert — {$this->businessName} ({$this->triggerReason})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.founder-alert',
        );
    }
}
