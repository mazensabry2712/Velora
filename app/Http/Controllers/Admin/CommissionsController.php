<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffCommission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Staff Commissions — cross-staff report endpoint.
 *
 * Routes:
 *   GET  /admin/api/commissions          – paginated list with filters
 *   GET  /admin/api/commissions/summary  – aggregate totals per staff
 *   PATCH /admin/api/commissions/{id}/mark-paid – mark a commission paid
 *   POST  /admin/api/commissions/bulk-mark-paid – bulk mark paid
 */
class CommissionsController extends Controller
{
    /**
     * GET /admin/api/commissions
     * All commissions, filterable by staff / paid status / date range.
     */
    public function index(Request $request): JsonResponse
    {
        $query = StaffCommission::with([
            'staff:id,first_name,last_name',
            'appointment:id,starts_at,status',
        ])->orderByDesc('created_at');

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->integer('staff_id'));
        }

        if ($request->filled('is_paid')) {
            $query->where('is_paid', filter_var($request->input('is_paid'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $perPage = min((int) $request->input('per_page', 25), 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * GET /admin/api/commissions/summary
     * Aggregate totals per staff member.
     */
    public function summary(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to',   now()->toDateString());

        $rows = StaffCommission::with('staff:id,first_name,last_name')
            ->whereBetween(\DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('
                staff_id,
                COUNT(*)                                        AS total_commissions,
                SUM(commission_amount)                          AS total_amount_cents,
                SUM(CASE WHEN is_paid = 1 THEN commission_amount ELSE 0 END) AS paid_cents,
                SUM(CASE WHEN is_paid = 0 THEN commission_amount ELSE 0 END) AS pending_cents
            ')
            ->groupBy('staff_id')
            ->get()
            ->map(fn($r) => [
                'staff'              => $r->staff,
                'total_commissions'  => $r->total_commissions,
                'total_amount'       => round($r->total_amount_cents / 100, 2),
                'paid'               => round($r->paid_cents        / 100, 2),
                'pending'            => round($r->pending_cents      / 100, 2),
            ]);

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'data'   => $rows,
        ]);
    }

    /**
     * PATCH /admin/api/commissions/{id}/mark-paid
     */
    public function markPaid(int $id): JsonResponse
    {
        $commission = StaffCommission::findOrFail($id);
        $commission->update(['is_paid' => true, 'paid_at' => now()]);

        return response()->json(['success' => true, 'data' => $commission->fresh()]);
    }

    /**
     * POST /admin/api/commissions/bulk-mark-paid
     * Body: { ids: [1, 2, 3] }  — or  { staff_id: 5 }  to mark all pending for a staff.
     */
    public function bulkMarkPaid(Request $request): JsonResponse
    {
        $request->validate([
            'ids'      => 'nullable|array',
            'ids.*'    => 'integer',
            'staff_id' => 'nullable|integer|exists:staff,id',
        ]);

        $query = StaffCommission::where('is_paid', false);

        if ($request->filled('ids')) {
            $query->whereIn('id', $request->input('ids'));
        } elseif ($request->filled('staff_id')) {
            $query->where('staff_id', $request->integer('staff_id'));
        } else {
            return response()->json(['message' => 'Provide ids or staff_id.'], 422);
        }

        $count = $query->count();
        $query->update(['is_paid' => true, 'paid_at' => now()]);

        return response()->json(['success' => true, 'marked_paid' => $count]);
    }
}
