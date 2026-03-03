<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsDaily;
use App\Models\BookingHeatmap;
use App\Models\ServiceAnalyticsDaily;
use App\Models\StaffAnalyticsDaily;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AnalyticsController — read-only API for pre-aggregated analytics tables.
 *
 * Routes (all under admin/api, auth + subscription middleware):
 *   GET /analytics/summary            — date range KPIs
 *   GET /analytics/daily              — daily time-series
 *   GET /analytics/heatmap            — booking heatmap (week/day/hour)
 *   GET /analytics/staff              — per-staff breakdown
 *   GET /analytics/services           — per-service breakdown
 */
class AnalyticsController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────
    // Summary
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/analytics/summary
     * Aggregated KPIs for a date range (defaults to last 30 days).
     *
     * @query from  Y-m-d  (default: 30 days ago)
     * @query to    Y-m-d  (default: today)
     */
    public function summary(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);

        $rows = AnalyticsDaily::whereBetween('date', [$from, $to])->get();

        $totals = [
            'period_from'         => $from,
            'period_to'           => $to,
            'total_bookings'      => $rows->sum('total_bookings'),
            'confirmed'           => $rows->sum('confirmed'),
            'completed'           => $rows->sum('completed'),
            'cancelled'           => $rows->sum('cancelled'),
            'no_shows'            => $rows->sum('no_shows'),
            'pending'             => $rows->sum('pending'),
            'new_customers'       => $rows->sum('new_customers'),
            'returning_customers' => $rows->sum('returning_customers'),
            'unique_customers'    => $rows->sum('unique_customers'),
            'gross_revenue'       => $rows->sum('gross_revenue'),
            'net_revenue'         => $rows->sum('net_revenue'),
            'online_bookings'     => $rows->sum('online_bookings'),
            'walkin_bookings'     => $rows->sum('walkin_bookings'),
            'completion_rate'     => $this->rate($rows->sum('completed'), $rows->sum('total_bookings')),
            'cancellation_rate'   => $this->rate($rows->sum('cancelled'), $rows->sum('total_bookings')),
            'no_show_rate'        => $this->rate($rows->sum('no_shows'), $rows->sum('total_bookings')),
            'avg_booking_value'   => $rows->sum('total_bookings') > 0
                ? round($rows->sum('gross_revenue') / $rows->sum('total_bookings'), 2)
                : 0,
        ];

        return response()->json(['success' => true, 'data' => $totals]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Daily time-series
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/analytics/daily
     * Daily granular data for charts.
     *
     * @query from   Y-m-d
     * @query to     Y-m-d
     * @query fields comma-separated list of columns to include (optional)
     */
    public function daily(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);

        $rows = AnalyticsDaily::whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        if ($request->filled('fields')) {
            $fields = array_merge(['date'], explode(',', $request->fields));
            $rows = $rows->map(fn ($r) => $r->only($fields));
        }

        return response()->json(['success' => true, 'data' => $rows]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Heatmap
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/analytics/heatmap
     * Returns all (day_of_week, hour_of_day, bookings_count) rows.
     * Client maps them to a 7×24 grid.
     *
     * @query weeks  int  How many past weeks to include (default: 12)
     */
    public function heatmap(Request $request): JsonResponse
    {
        $weeks     = max(1, min(52, (int) ($request->get('weeks', 12))));
        $weekStart = Carbon::now()->subWeeks($weeks)->startOfWeek()->toDateString();

        $rows = BookingHeatmap::where('week_start', '>=', $weekStart)
            ->selectRaw('day_of_week, hour_of_day, SUM(bookings_count) AS bookings_count, SUM(revenue_cents) AS revenue_cents')
            ->groupBy('day_of_week', 'hour_of_day')
            ->orderBy('day_of_week')
            ->orderBy('hour_of_day')
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Per-staff
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/analytics/staff
     * Aggregated per-staff metrics for a date range.
     *
     * @query from     Y-m-d
     * @query to       Y-m-d
     * @query staff_id int  (optional — single staff breakdown)
     */
    public function staff(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);

        $query = StaffAnalyticsDaily::with('staff:id,name,email')
            ->whereBetween('date', [$from, $to]);

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        $rows = $query->get();

        // Group by staff and aggregate
        $grouped = $rows->groupBy('staff_id')->map(function ($staffRows) {
            $first = $staffRows->first();
            return [
                'staff_id'          => $first->staff_id,
                'staff_name'        => $first->staff?->name ?? 'Unknown',
                'bookings_count'    => $staffRows->sum('bookings_count'),
                'completed'         => $staffRows->sum('completed'),
                'cancelled'         => $staffRows->sum('cancelled'),
                'no_shows'          => $staffRows->sum('no_shows'),
                'revenue'           => $staffRows->sum('revenue'),
                'commission_earned' => $staffRows->sum('commission_earned'),
                'unique_customers'  => $staffRows->sum('unique_customers'),
                'completion_rate'   => $this->rate($staffRows->sum('completed'), $staffRows->sum('bookings_count')),
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $grouped]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Per-service
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/analytics/services
     * Aggregated per-service metrics for a date range.
     *
     * @query from       Y-m-d
     * @query to         Y-m-d
     * @query service_id int (optional)
     */
    public function services(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);

        $query = ServiceAnalyticsDaily::with('service:id,name,price')
            ->whereBetween('date', [$from, $to]);

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        $rows = $query->get();

        $grouped = $rows->groupBy('service_id')->map(function ($sRows) {
            $first = $sRows->first();
            return [
                'service_id'        => $first->service_id,
                'service_name'      => $first->service?->name ?? 'Unknown',
                'bookings_count'    => $sRows->sum('bookings_count'),
                'completed'         => $sRows->sum('completed'),
                'cancelled'         => $sRows->sum('cancelled'),
                'revenue'           => $sRows->sum('revenue'),
                'avg_booking_value' => $sRows->sum('bookings_count') > 0
                    ? round($sRows->sum('revenue') / $sRows->sum('bookings_count'), 2)
                    : 0,
                'completion_rate'   => $this->rate($sRows->sum('completed'), $sRows->sum('bookings_count')),
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $grouped]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    /** @return array{string, string} [from, to] date strings */
    private function dateRange(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->from)->toDateString()
            : Carbon::now()->subDays(29)->toDateString();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->toDateString()
            : Carbon::today()->toDateString();

        return [$from, $to];
    }

    private function rate(int|float $part, int|float $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 1) : 0.0;
    }
}
