<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Reporting\Actions\GetDashboardReport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ReportController extends Controller
{
    public function __construct(
        private readonly GetDashboardReport $dashboardReport,
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:today,week,month,year,all,custom'],
            'start_date' => ['nullable', 'date', 'required_if:period,custom'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'required_if:period,custom'],
        ]);

        $data = $this->dashboardReport->execute(
            $validated['period'] ?? 'month',
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );

        return view('admin.reports.index', $data);
    }

    public function exportAppointments(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:today,week,month,year,all,custom'],
            'start_date' => ['nullable', 'date', 'required_if:period,custom'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'required_if:period,custom'],
        ]);

        $period = $validated['period'] ?? 'month';
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;
        $fileName = 'appointments-' . $period . '-' . now()->format('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AppointmentsExport(tenant(), $period, $startDate, $endDate),
            $fileName
        );
    }
}
