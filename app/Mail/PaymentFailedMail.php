<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the tenant admin when Stripe reports invoice.payment_failed.
 * Gives them 3 days (grace period) to update their payment method.
 */
class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $businessName,
        public readonly string $ownerEmail,
        public readonly string $invoiceId,
        public readonly string $billingPortalUrl,
        public readonly int    $graceDays = 3,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ فشل تجديد اشتراكك في Velora — يرجى تحديث بيانات الدفع',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-failed',
        );
    }
}
