<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * TrialNudgeMail — sent on Day 1, 3, 7, 12 of the free trial.
 *
 * Each nudge day has its own subject and view partial.
 * Subject and messaging are framed in terms of VALUE, not features.
 */
class TrialNudgeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var array<int, string> */
    protected static array $subjects = [
        1  => "🎉 Your Velora account is ready — start in 5 minutes",
        3  => "❓ Getting value from Velora yet?",
        7  => "📊 Your first week report is ready",
        12 => "⏰ 2 days left — keep your bookings running",
    ];

    public function __construct(
        public readonly int    $nudgeDay,
        public readonly string $businessName,
        public readonly string $ownerEmail,
        public readonly string $tenantId,
        public readonly string $bookingUrl,
        public readonly int    $appointmentsCount = 0,
        public readonly int    $remindersCount    = 0,
        public readonly float  $savedAmount       = 0,
        public readonly int    $trialDaysLeft     = 14,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: self::$subjects[$this->nudgeDay] ?? 'Update from Velora',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-nudge',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
