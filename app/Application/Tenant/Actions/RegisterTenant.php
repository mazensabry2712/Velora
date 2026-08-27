<?php

declare(strict_types=1);

namespace App\Application\Tenant\Actions;

use App\Domain\Tenant\Contracts\TenantRegistrar;

final class RegisterTenant
{
    public function __construct(
        private readonly TenantRegistrar $registration,
    ) {}

    /** @return array<string, mixed> */
    public function execute(array $data): array
    {
        return $this->registration->register($data);
    }
}
