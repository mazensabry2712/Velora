<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\Customer;
use App\Services\ReportService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
#[Group('reconciliation')]
final class ReportingReconciliationScenarioTest extends TenantTestCase
{
    #[Test]
    public function report_customer_metric_uses_the_same_canonical_customer_population_as_dashboard(): void
    {
        Customer::create([
            'first_name' => 'Report',
            'last_name' => 'Reconciliation',
            'email' => 'qa-report-reconciliation@example.com',
            'phone' => '+201000000006',
            'is_blocked' => false,
        ]);

        $data = app(ReportService::class)->getDashboardData('month');

        $this->assertSame(Customer::count(), $data['stats']['total_customers']);
        $this->assertSame($this->getTenantDashboardCustomerCount(), $data['stats']['total_customers']);
    }

    private function getTenantDashboardCustomerCount(): int
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();

        return (int) $response->viewData('stats')['customers'];
    }
}
