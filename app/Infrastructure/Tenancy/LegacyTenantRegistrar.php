<?php

declare(strict_types=1);

namespace App\Infrastructure\Tenancy;

use App\Domain\Tenant\Contracts\TenantRegistrar;
use App\Services\TenantRegistrationService;

final class LegacyTenantRegistrar implements TenantRegistrar
{
    public function __construct(
        private readonly TenantRegistrationService $registration,
    ) {}

    /** @return array<string, mixed> */
    public function register(array $data): array
    {
        return $this->registration->register($data);
    }
}
