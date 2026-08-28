<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Tenant;

final class TenantProvisioningRequested
{
    public function __construct(public readonly Tenant $tenant) {}
}
