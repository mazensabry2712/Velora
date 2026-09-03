<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Exports\AppointmentsExport;
use App\Models\Appointment;
use App\Services\ReportService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
#[Group('reconciliation')]
#[Group('reporting')]
final class ReportingCanonicalRangeScenarioTest extends TenantTestCase
{
    #[Test]
    public function reports_and_exports_use_canonical_starts_at_for_custom_ranges(): void
    {
        $inside = Appointment::create([
            'customer_id_new' => $this->customerProfile->id,
            'staff_id_new' => $this->staff->id,
            'service_id' => $this->service->id,
            'service_type' => 'Consultation',
            'status' => Appointment::STATUS_CONFIRMED,
            'starts_at' => now()->startOfMonth()->addDays(4)->setTime(10, 0),
            'ends_at' => now()->startOfMonth()->addDays(4)->setTime(10, 30),
            'price' => 100,
        ]);

        $outside = Appointment::create([
            'customer_id_new' => $this->customerProfile->id,
            'staff_id_new' => $this->staff->id,
            'service_id' => $this->service->id,
            'service_type' => 'Follow-up',
            'status' => Appointment::STATUS_CONFIRMED,
            'starts_at' => now()->subMonth()->startOfMonth()->setTime(10, 0),
            'ends_at' => now()->subMonth()->startOfMonth()->setTime(10, 30),
            'price' => 100,
        ]);

        [$start, $end] = app(ReportService::class)->resolveRange(
            'custom',
            $inside->starts_at->toDateString(),
            $inside->starts_at->toDateString(),
        );

        $report = app(ReportService::class)->getDashboardData(
            'custom',
            $start->toDateString(),
            $end->toDateString(),
        );

        $this->assertSame(1, $report['stats']['total_appointments']);
        $this->assertSame(1, $report['stats']['confirmed_appointments']);
        $this->assertSame('Consultation', $report['serviceTypes']->first()->service_type);

        $exportedIds = (new AppointmentsExport(
            tenant(),
            'custom',
            $start->toDateString(),
            $end->toDateString(),
        ))->query()->pluck('id')->all();

        $this->assertSame([$inside->id], array_map('intval', $exportedIds));
        $this->assertNotContains($outside->id, $exportedIds);
    }

    #[Test]
    public function report_controller_rejects_an_invalid_custom_date_range(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('admin.reports', [
            'period' => 'custom',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-01',
        ]))->assertSessionHasErrors('end_date');
    }
}
