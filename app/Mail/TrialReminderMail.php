<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $type,          // '3day_warning' | 'grace_warning'
        public readonly string $businessName,
        public readonly string $tenantId,
        public readonly int    $daysLeft,
        public readonly mixed  $trialEndsAt,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            '3day_warning' => "⏰ Your Velora trial ends in {$this->daysLeft} day(s) — Upgrade now",
            'grace_warning' => "🚨 Action required: Your Velora account access expires in {$this->daysLeft} day(s)",
            default         => 'Velora Subscription Reminder',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.trial-reminder');
    }

    public function attachments(): array
    {
        return [];
    }
}
