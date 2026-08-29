<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Contracts;

interface WhatsAppProvider
{
    /**
     * @param array<string, scalar|null> $payload
     */
    public function send(string $recipient, string $message, array $payload = []): WhatsAppSendResult;
}
