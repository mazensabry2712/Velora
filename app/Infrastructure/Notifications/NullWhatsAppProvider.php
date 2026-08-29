<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Domain\Notifications\Contracts\WhatsAppProvider;
use App\Domain\Notifications\Contracts\WhatsAppSendResult;

final class NullWhatsAppProvider implements WhatsAppProvider
{
    public function send(string $recipient, string $message, array $payload = []): WhatsAppSendResult
    {
        return WhatsAppSendResult::skipped('WhatsApp provider is not configured.');
    }
}
