<?php

declare(strict_types=1);

namespace App\Application\Tenant\Actions;

use App\Services\TenantRegistrationService;

final class RegisterTenant
{
    public function __construct(
        private readonly TenantRegistrationService $registration,
    ) {}

    /**
     * Execute the tenant onboarding use case.
     * Transport/HTTP concerns stay outside the application layer.
     *
     * @return array<string, mixed>
     */
    public function execute(array $data): array
    {
        return $this->registration->register($data);
    }
}
