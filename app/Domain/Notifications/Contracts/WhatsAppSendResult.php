<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Contracts;

final readonly class WhatsAppSendResult
{
    public function __construct(
        public string $status,
        public ?string $providerMessageId = null,
        public ?string $error = null,
    ) {}

    public static function skipped(string $reason): self
    {
        return new self('skipped', null, $reason);
    }

    public static function sent(?string $providerMessageId = null): self
    {
        return new self('sent', $providerMessageId);
    }

    public static function failed(string $error): self
    {
        return new self('failed', null, $error);
    }
}
