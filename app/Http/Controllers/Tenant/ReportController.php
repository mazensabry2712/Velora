<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Queue;
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
     * Dashboard statistics for Admin Tenant.
     * NOTE: No tenant_id filter needed — each tenant has their own isolated DB.
     */
    public function dashboard(Request $request)
    {
        $period = $request->input('period', 'today'); // today, week, month

        $stats = [
            'appointments'     => $this->getAppointmentStats($period),
            'peak_hours'       => $this->getPeakHours($period),
            'staff_performance'=> $this->getStaffPerformance($period),
            'revenue'          => $this->getRevenueStats($period),
            'queue_stats'      => $this->getQueueStats($period),
        ];

        return response()->json([
            'success' => true,
            'period'  => $period,
            'data'    => $stats,
        ]);
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Build a base Appointment query filtered by $period.
     * Uses starts_at (the actual column name in appointments table).
     */
    private function appointmentQueryForPeriod(string $period)
    {
        $q = Appointment::query();
        return match ($period) {
            'today' => $q->whereDate('starts_at', now()),
            'week'  => $q->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $q->whereMonth('starts_at', now()->month)->whereYear('starts_at', now()->year),
            default => $q,
        };
    }

    private function queueQueryForPeriod(string $period)
    {
        $q = Queue::query();
        return match ($period) {
            'today' => $q->whereDate('created_at', now()),
            'week'  => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            default => $q,
        };
    }

    private function invoiceQueryForPeriod(string $period)
    {
        $q = Invoice::query();
        return match ($period) {
            'today' => $q->whereDate('created_at', now()),
            'week'  => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            default => $q,
        };
    }

    private function getAppointmentStats(string $period): array
    {
        $query = $this->appointmentQueryForPeriod($period);

        $total     = (clone $query)->count();
        $confirmed = (clone $query)->where('status', Appointment::STATUS_CONFIRMED)->count();
        $pending   = (clone $query)->where('status', Appointment::STATUS_PENDING)->count();
        $cancelled = (clone $query)->where('status', Appointment::STATUS_CANCELLED)->count();
        $completed = (clone $query)->where('status', Appointment::STATUS_COMPLETED)->count();
        $noShow    = (clone $query)->where('status', Appointment::STATUS_NO_SHOW)->count();

        $dailyBreakdown = $this->appointmentQueryForPeriod($period)
            ->select(
                DB::raw('DATE(starts_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as confirmed'),
                DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total'             => $total,
            'confirmed'         => $confirmed,
            'pending'           => $pending,
            'cancelled'         => $cancelled,
            'completed'         => $completed,
            'no_show'           => $noShow,
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 2) : 0,
            'confirmation_rate' => $total > 0 ? round(($confirmed / $total) * 100, 2) : 0,
            'completion_rate'   => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'daily_breakdown'   => $dailyBreakdown,
        ];
    }

    private function getPeakHours(string $period): array
    {
        $hourlyDistribution = $this->appointmentQueryForPeriod($period)
            ->select(
                DB::raw('HOUR(starts_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderByDesc('count')
            ->get();

        $peakHour = $hourlyDistribution->first();

        return [
            'peak_hour'           => $peakHour ? str_pad($peakHour->hour, 2, '0', STR_PAD_LEFT) . ':00' : null,
            'peak_hour_count'     => $peakHour?->count ?? 0,
            'hourly_distribution' => $hourlyDistribution,
            'busiest_time'        => $this->getBusiestTimeOfDay($hourlyDistribution),
        ];
    }

    private function getStaffPerformance(string $period): \Illuminate\Support\Collection
    {
        return $this->appointmentQueryForPeriod($period)
            ->select(
                'staff_id',
                DB::raw('COUNT(*) as total_appointments'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled'),
                DB::raw('SUM(CASE WHEN status = "no_show"   THEN 1 ELSE 0 END) as no_show')
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
    }

    private function getRevenueStats(string $period): array
    {
        $query = $this->invoiceQueryForPeriod($period);

        $total   = (clone $query)->sum('amount');
        $paid    = (clone $query)->where('status', 'paid')->sum('amount');
        $pending = (clone $query)->where('status', 'pending')->sum('amount');
        $count   = (clone $query)->count();

        return [
            'total_revenue'   => round($total, 2),
            'paid'            => round($paid, 2),
            'pending'         => round($pending, 2),
            'invoice_count'   => $count,
            'average_invoice' => $count > 0 ? round($total / $count, 2) : 0,
        ];
    }

    private function getQueueStats(string $period): array
    {
        $query = $this->queueQueryForPeriod($period);

        $total       = (clone $query)->count();
        $served      = (clone $query)->where('status', 'completed')->count();
        $skipped     = (clone $query)->whereIn('status', ['cancelled', 'skipped'])->count();
        $avgWaitTime = (clone $query)->where('status', 'completed')->avg('estimated_wait_time');

        return [
            'total_queues'     => $total,
            'completed'        => $served,
            'cancelled'        => $skipped,
            'skip_rate'        => $total > 0 ? round(($skipped / $total) * 100, 2) : 0,
            'average_wait_time'=> round($avgWaitTime ?? 0, 2),
        ];
    }

    /**
     * Export appointments report as PDF
     */
    public function exportAppointmentsPDF(Request $request)
    {
        $period = $request->input('period', 'month');

        $appointments = $this->appointmentQueryForPeriod($period)
            ->with(['customer', 'staff'])
            ->orderBy('starts_at')
            ->get();

        $stats = $this->getAppointmentStats($period);

        $pdf = Pdf::loadView('pdf.appointments-report', [
            'tenant'       => tenant(),
            'appointments' => $appointments,
            'stats'        => $stats,
            'period'       => $period,
            'generated_at' => now(),
        ]);

        return $pdf->download('appointments-report-' . $period . '-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export invoice as PDF
     */
    public function exportInvoicePDF(int $id)
    {
        $invoice = Invoice::with(['customer', 'appointment', 'items'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.invoice', [
            'tenant'  => tenant(),
            'invoice' => $invoice,
        ]);

        return $pdf->download('invoice-' . ($invoice->number ?? $id) . '.pdf');
    }

    private function getBusiestTimeOfDay($hourlyDistribution): ?string
    {
        if ($hourlyDistribution->isEmpty()) {
            return null;
        }

        $h = $hourlyDistribution->first()->hour;

        return match (true) {
            $h >= 6  && $h < 12 => 'morning',
            $h >= 12 && $h < 17 => 'afternoon',
            $h >= 17 && $h < 21 => 'evening',
            default              => 'night',
        };
    }

    /**
     * Export appointments as Excel/CSV
     */
    public function exportAppointmentsCSV(Request $request)
    {
        $period    = $request->input('period', 'month');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $fileName  = 'appointments-' . $period . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new AppointmentsExport(tenant(), $period, $startDate, $endDate),
            $fileName
        );
    }

    /**
     * Export invoices as Excel/CSV
     */
    public function exportInvoicesCSV(Request $request)
    {
        $period   = $request->input('period', 'month');
        $fileName = 'invoices-' . $period . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new InvoicesExport(tenant(), $period),
            $fileName
        );
    }
}

