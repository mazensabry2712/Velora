<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsDaily;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant V1 API — Analytics
 *
 * Accessed via token-based middleware (X-Tenant-Token + Sanctum).
 * Mirrors the admin AnalyticsController but is scoped to
 * the authenticated tenant's own database context.
 */
class AnalyticsController extends Controller
{
    /**
     * GET /v1/analytics/summary
     * High-level KPIs for a date range.
     */
    public function summary(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->input('from', now()->subDays(30)->toDateString()))->startOfDay();
        $to   = Carbon::parse($request->input('to',   now()->toDateString()))->endOfDay();

        $appointments = Appointment::whereBetween('starts_at', [$from, $to]);

        $total     = (clone $appointments)->count();
        $completed = (clone $appointments)->where('status', Appointment::STATUS_COMPLETED)->count();
        $cancelled = (clone $appointments)->where('status', Appointment::STATUS_CANCELLED)->count();
        $noShow    = (clone $appointments)->where('status', Appointment::STATUS_NO_SHOW)->count();

        return response()->json([
            'period' => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
            'totals' => [
                'appointments'      => $total,
                'completed'         => $completed,
                'cancelled'         => $cancelled,
                'no_show'           => $noShow,
                'completion_rate'   => $total > 0 ? round($completed / $total * 100, 1) : 0,
                'cancellation_rate' => $total > 0 ? round($cancelled / $total * 100, 1) : 0,
                'no_show_rate'      => $total > 0 ? round($noShow    / $total * 100, 1) : 0,
            ],
        ]);
    }

    /**
     * GET /v1/analytics/daily
     * Time-series data from pre-aggregated analytics_daily table.
     */
    public function daily(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to',   now()->toDateString());

        $rows = AnalyticsDaily::whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get([
                'date',
                'total_appointments',
                'completed_appointments',
                'cancelled_appointments',
                'no_show_appointments',
                'new_customers',
                'total_revenue',
                'avg_appointment_value',
            ]);

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'data'   => $rows,
        ]);
    }
}
