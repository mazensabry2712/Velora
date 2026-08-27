<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Contracts;

interface TenantRegistrar
{
    /** @return array<string, mixed> */
    public function register(array $data): array;
}
