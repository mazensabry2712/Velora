<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reports)
    {
    }

    /**
     * Reports dashboard. Accepts an optional ?period= (today|week|month|
     * year|all|custom) plus ?start_date=&end_date= when period=custom,
     * and scopes every activity-based metric to that range.
     */
    public function index(Request $request)
    {
        $data = $this->reports->getDashboardData(
            $request->get('period', 'month'),
            $request->get('start_date'),
            $request->get('end_date'),
        );

        return view('admin.reports.index', $data);
    }

    public function exportAppointments(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $period    = $request->get('period', 'month');
        $startDate = $request->get('start_date');
        $endDate   = $request->get('end_date');
        $fileName  = 'appointments-' . $period . '-' . now()->format('Y-m-d') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AppointmentsExport(tenant(), $period, $startDate, $endDate),
            $fileName
        );
    }
}
