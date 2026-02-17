<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Queue;
use App\Models\User;

class QueueUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $queue;
    public $user;
    public $locale;
    public $updateType; // 'next', 'position_update', 'ready'

    /**
     * Create a new message instance.
     */
    public function __construct(Queue $queue, User $user, string $updateType, string $locale = 'en')
    {
        $this->queue = $queue;
        $this->user = $user;
        $this->updateType = $updateType;
        $this->locale = $locale;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = __('notifications.queue_update.subject', [], $this->locale);

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.queue-update',
            with: [
                'queue' => $this->queue,
                'user' => $this->user,
                'updateType' => $this->updateType,
                'locale' => $this->locale,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
