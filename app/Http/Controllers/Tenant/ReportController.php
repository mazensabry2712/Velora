<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Queue;
use App\Models\User;
use App\Models\Invoice;
use App\Exports\AppointmentsExport;
use App\Exports\InvoicesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Dashboard statistics for Admin Tenant
     */
    public function dashboard(Request $request)
    {
        $tenant = tenant();
        $period = $request->input('period', 'today'); // today, week, month

        $stats = [
            'appointments' => $this->getAppointmentStats($tenant, $period),
            'peak_hours' => $this->getPeakHours($tenant, $period),
            'staff_performance' => $this->getStaffPerformance($tenant, $period),
            'revenue' => $this->getRevenueStats($tenant, $period),
            'queue_stats' => $this->getQueueStats($tenant, $period),
        ];

        return response()->json([
            'success' => true,
            'period' => $period,
            'data' => $stats,
        ]);
    }

    /**
     * Get appointment statistics (daily/weekly/monthly)
     */
    private function getAppointmentStats($tenant, $period)
    {
        $query = Appointment::where('tenant_id', $tenant->id);

        // Apply date filter based on period
        switch ($period) {
            case 'today':
                $query->whereDate('appointment_date', now());
                break;
            case 'week':
                $query->whereBetween('appointment_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'month':
                $query->whereMonth('appointment_date', now()->month)
                    ->whereYear('appointment_date', now()->year);
                break;
        }

        $total = $query->count();
        $confirmed = (clone $query)->where('status', 'Confirmed')->count();
        $pending = (clone $query)->where('status', 'Pending')->count();
        $cancelled = (clone $query)->where('status', 'Cancelled')->count();
        $completed = (clone $query)->where('status', 'Completed')->count();

        // Get daily breakdown for charts
        $dailyBreakdown = Appointment::where('tenant_id', $tenant->id)
            ->when($period === 'week', function ($q) {
                $q->whereBetween('appointment_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
            })
            ->when($period === 'month', function ($q) {
                $q->whereMonth('appointment_date', now()->month)
                    ->whereYear('appointment_date', now()->year);
            })
            ->select(
                DB::raw('DATE(appointment_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN status = "Confirmed" THEN 1 ELSE 0 END) as confirmed'),
                DB::raw('SUM(CASE WHEN status = "Cancelled" THEN 1 ELSE 0 END) as cancelled')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'pending' => $pending,
            'cancelled' => $cancelled,
            'completed' => $completed,
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 2) : 0,
            'confirmation_rate' => $total > 0 ? round(($confirmed / $total) * 100, 2) : 0,
            'daily_breakdown' => $dailyBreakdown,
        ];
    }

    /**
     * Get peak hours analysis
     */
    private function getPeakHours($tenant, $period)
    {
        $query = Appointment::where('tenant_id', $tenant->id);

        // Apply date filter
        switch ($period) {
            case 'today':
                $query->whereDate('appointment_date', now());
                break;
            case 'week':
                $query->whereBetween('appointment_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'month':
                $query->whereMonth('appointment_date', now()->month)
                    ->whereYear('appointment_date', now()->year);
                break;
        }

        $hourlyDistribution = (clone $query)
            ->select(
                DB::raw('HOUR(appointment_date) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->get();

        $peakHour = $hourlyDistribution->first();

        return [
            'peak_hour' => $peakHour ? $peakHour->hour . ':00' : null,
            'peak_hour_count' => $peakHour ? $peakHour->count : 0,
            'hourly_distribution' => $hourlyDistribution,
            'busiest_time' => $this->getBusiestTimeOfDay($hourlyDistribution),
        ];
    }

    /**
     * Get staff performance
     */
    private function getStaffPerformance($tenant, $period)
    {
        $query = Appointment::where('tenant_id', $tenant->id);

        // Apply date filter
        switch ($period) {
            case 'today':
                $query->whereDate('appointment_date', now());
                break;
            case 'week':
                $query->whereBetween('appointment_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'month':
                $query->whereMonth('appointment_date', now()->month)
                    ->whereYear('appointment_date', now()->year);
                break;
        }

        $staffPerformance = $query
            ->select(
                'staff_id',
                DB::raw('COUNT(*) as total_appointments'),
                DB::raw('SUM(CASE WHEN status = "Completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "Cancelled" THEN 1 ELSE 0 END) as cancelled'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_response_time')
            )
            ->groupBy('staff_id')
            ->with('staff:id,name,email')
            ->get()
            ->map(function ($item) {
                $item->completion_rate = $item->total_appointments > 0
                    ? round(($item->completed / $item->total_appointments) * 100, 2)
                    : 0;
                return $item;
            });

        return $staffPerformance;
    }

    /**
     * Get revenue statistics
     */
    private function getRevenueStats($tenant, $period)
    {
        $query = Invoice::where('tenant_id', $tenant->id);

        // Apply date filter
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', now());
                break;
            case 'week':
                $query->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
                break;
        }

        $total = (clone $query)->sum('total_amount');
        $paid = (clone $query)->where('status', 'Paid')->sum('total_amount');
        $pending = (clone $query)->where('status', 'Pending')->sum('total_amount');
        $count = $query->count();

        return [
            'total_revenue' => $total,
            'paid' => $paid,
            'pending' => $pending,
            'invoice_count' => $count,
            'average_invoice' => $count > 0 ? round($total / $count, 2) : 0,
        ];
    }

    /**
     * Get queue statistics
     */
    private function getQueueStats($tenant, $period)
    {
        $query = Queue::where('tenant_id', $tenant->id);

        // Apply date filter
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', now());
                break;
            case 'week':
                $query->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
                break;
        }

        $total = $query->count();
        $served = (clone $query)->where('status', 'completed')->count();
        $skipped = (clone $query)->where('status', 'cancelled')->count();
        $avgWaitTime = (clone $query)->where('status', 'completed')->avg('estimated_wait_time');

        return [
            'total_queues' => $total,
            'completed' => $served,
            'cancelled' => $skipped,
            'skip_rate' => $total > 0 ? round(($skipped / $total) * 100, 2) : 0,
            'average_wait_time' => round($avgWaitTime ?? 0, 2),
        ];
    }

    /**
     * Export appointments report as PDF
     */
    public function exportAppointmentsPDF(Request $request)
    {
        $tenant = tenant();
        $period = $request->input('period', 'month');

        $query = Appointment::where('tenant_id', $tenant->id)
            ->with(['customer', 'staff']);

        // Apply date filter
        switch ($period) {
            case 'today':
                $query->whereDate('appointment_date', now());
                break;
            case 'week':
                $query->whereBetween('appointment_date', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'month':
                $query->whereMonth('appointment_date', now()->month)
                    ->whereYear('appointment_date', now()->year);
                break;
        }

        $appointments = $query->orderBy('appointment_date')->get();
        $stats = $this->getAppointmentStats($tenant, $period);

        $pdf = Pdf::loadView('pdf.appointments-report', [
            'tenant' => $tenant,
            'appointments' => $appointments,
            'stats' => $stats,
            'period' => $period,
            'generated_at' => now(),
        ]);

        return $pdf->download('appointments-report-' . $period . '-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export invoice as PDF
     */
    public function exportInvoicePDF($id)
    {
        $tenant = tenant();
        $invoice = Invoice::where('tenant_id', $tenant->id)
            ->with(['customer', 'appointment'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.invoice', [
            'tenant' => $tenant,
            'invoice' => $invoice,
        ]);

        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Helper: Determine busiest time of day
     */
    private function getBusiestTimeOfDay($hourlyDistribution)
    {
        if ($hourlyDistribution->isEmpty()) {
            return null;
        }

        $maxHour = $hourlyDistribution->first()->hour;

        if ($maxHour >= 6 && $maxHour < 12) {
            return 'morning'; // 6 AM - 12 PM
        } elseif ($maxHour >= 12 && $maxHour < 17) {
            return 'afternoon'; // 12 PM - 5 PM
        } elseif ($maxHour >= 17 && $maxHour < 21) {
            return 'evening'; // 5 PM - 9 PM
        } else {
            return 'night'; // 9 PM - 6 AM
        }
    }

    /**
     * Export appointments as CSV
     */
    public function exportAppointmentsCSV(Request $request)
    {
        $tenant = tenant();
        $period = $request->input('period', 'month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $fileName = 'appointments-' . $period . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new AppointmentsExport($tenant, $period, $startDate, $endDate),
            $fileName
        );
    }

    /**
     * Export invoices as CSV
     */
    public function exportInvoicesCSV(Request $request)
    {
        $tenant = tenant();
        $period = $request->input('period', 'month');

        $fileName = 'invoices-' . $period . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new InvoicesExport($tenant, $period),
            $fileName
        );
    }
}

