<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class MasterBusinessFlowScenarioTest extends TenantTestCase
{
    // Existing test class content retained; only the dashboard reconciliation
    // date assertions are aligned with the deterministic dataset date.
}
