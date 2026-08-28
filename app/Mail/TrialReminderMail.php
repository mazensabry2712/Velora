<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class TrialReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $type,
        public readonly string $businessName,
        public readonly string $tenantId,
        public readonly int $daysLeft,
        public readonly mixed $trialEndsAt,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            '3day_warning' => "⏰ Your Velora trial ends in {$this->daysLeft} day(s) — Upgrade now",
            'read_only_warning' => "🔒 Your Velora account becomes read-only in {$this->daysLeft} day(s)",
            'lock_warning' => "🚨 Your Velora account will lock in {$this->daysLeft} day(s)",
            'deletion_warning' => "⚠️ Your Velora account will be permanently deleted in {$this->daysLeft} day(s)",
            default => 'Velora Subscription Reminder',
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
