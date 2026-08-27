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
        $data = $this->dashboardReport->execute(
            (string) $request->get('period', 'month'),
            $request->get('start_date'),
            $request->get('end_date'),
        );

        return view('admin.reports.index', $data);
    }

    public function exportAppointments(Request $request): BinaryFileResponse
    {
        $period = (string) $request->get('period', 'month');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $fileName = 'appointments-' . $period . '-' . now()->format('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AppointmentsExport(tenant(), $period, $startDate, $endDate),
            $fileName
        );
    }
}
