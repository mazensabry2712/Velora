<?php

declare(strict_types=1);

namespace App\Domain\Queue\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class QueueLifecycleNotificationRequested implements ShouldDispatchAfterCommit
{
    public function __construct(
        public string $tenantId,
        public int $queueId,
        public int $appointmentId,
        public string $publicReference,
        public string $event,
        public string $updateType,
        public string $queueNumber,
        public ?int $position,
        public ?int $oldPosition,
        public string $customerType,
        public int $customerId,
        public string $customerName,
        public ?string $email,
        public ?string $phone,
        public string $locale,
        public string $eventId,
    ) {}
}
