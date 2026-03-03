<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffBreak;
use App\Models\StaffCommission;
use App\Models\StaffTimeOff;
use App\Models\StaffWorkingHours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StaffScheduleController — manage per-staff working hours, breaks, time-off, and commissions.
 *
 * Routes (all under admin/api, protected by auth + role middleware):
 *   GET    /staff/{id}/working-hours
 *   PUT    /staff/{id}/working-hours
 *   GET    /staff/{id}/breaks
 *   POST   /staff/{id}/breaks
 *   PUT    /staff/{id}/breaks/{breakId}
 *   DELETE /staff/{id}/breaks/{breakId}
 *   GET    /staff/{id}/time-off
 *   POST   /staff/{id}/time-off
 *   PUT    /staff/{id}/time-off/{timeOffId}
 *   DELETE /staff/{id}/time-off/{timeOffId}
 *   PATCH  /staff/{id}/time-off/{timeOffId}/status
 *   GET    /staff/{id}/commissions
 */
class StaffScheduleController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────
    // Working Hours
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/staff/{id}/working-hours
     * Return all 7 days working-hour rows (0=Sunday … 6=Saturday).
     */
    public function workingHours(int $id): JsonResponse
    {
        $staff = Staff::findOrFail($id);

        $rows = StaffWorkingHours::where('staff_id', $staff->id)
            ->orderBy('day_of_week')
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    /**
     * PUT /admin/api/staff/{id}/working-hours
     * Bulk-upsert all 7 days in one request.
     *
     * Body: { "days": [ { "day_of_week":0, "is_working":true, "start_time":"09:00", "end_time":"17:00" }, … ] }
     */
    public function saveWorkingHours(Request $request, int $id): JsonResponse
    {
        $staff = Staff::findOrFail($id);

        $validated = $request->validate([
            'days'                  => 'required|array|min:1|max:7',
            'days.*.day_of_week'    => 'required|integer|min:0|max:6',
            'days.*.is_working'     => 'required|boolean',
            'days.*.start_time'     => 'nullable|date_format:H:i',
            'days.*.end_time'       => 'nullable|date_format:H:i|after:days.*.start_time',
        ]);

        DB::transaction(function () use ($staff, $validated) {
            foreach ($validated['days'] as $day) {
                StaffWorkingHours::updateOrCreate(
                    ['staff_id' => $staff->id, 'day_of_week' => $day['day_of_week']],
                    [
                        'is_working' => $day['is_working'],
                        'start_time' => $day['is_working'] ? ($day['start_time'] ?? null) : null,
                        'end_time'   => $day['is_working'] ? ($day['end_time']   ?? null) : null,
                    ]
                );
            }
        });

        $rows = StaffWorkingHours::where('staff_id', $staff->id)->orderBy('day_of_week')->get();
        return response()->json(['success' => true, 'data' => $rows]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Breaks
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/staff/{id}/breaks
     */
    public function breaks(int $id): JsonResponse
    {
        $staff = Staff::findOrFail($id);

        $breaks = StaffBreak::where('staff_id', $staff->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json(['success' => true, 'data' => $breaks]);
    }

    /**
     * POST /admin/api/staff/{id}/breaks
     * Body: { "day_of_week":1, "start_time":"12:00", "end_time":"13:00", "label":"Lunch" }
     */
    public function storeBreak(Request $request, int $id): JsonResponse
    {
        $staff = Staff::findOrFail($id);

        $validated = $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'label'       => 'nullable|string|max:100',
        ]);

        $break = StaffBreak::create(array_merge($validated, ['staff_id' => $staff->id]));

        return response()->json(['success' => true, 'data' => $break], 201);
    }

    /**
     * PUT /admin/api/staff/{id}/breaks/{breakId}
     */
    public function updateBreak(Request $request, int $id, int $breakId): JsonResponse
    {
        $staff = Staff::findOrFail($id);
        $break = StaffBreak::where('staff_id', $staff->id)->findOrFail($breakId);

        $validated = $request->validate([
            'day_of_week' => 'sometimes|integer|min:0|max:6',
            'start_time'  => 'sometimes|date_format:H:i',
            'end_time'    => 'sometimes|date_format:H:i',
            'label'       => 'nullable|string|max:100',
        ]);

        $break->update($validated);

        return response()->json(['success' => true, 'data' => $break]);
    }

    /**
     * DELETE /admin/api/staff/{id}/breaks/{breakId}
     */
    public function destroyBreak(int $id, int $breakId): JsonResponse
    {
        $staff = Staff::findOrFail($id);
        $break = StaffBreak::where('staff_id', $staff->id)->findOrFail($breakId);
        $break->delete();

        return response()->json(['success' => true, 'message' => __('Break deleted')]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Time-Off
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/staff/{id}/time-off
     * Optional: ?status=pending|approved|rejected&from=Y-m-d&to=Y-m-d
     */
    public function timeOff(Request $request, int $id): JsonResponse
    {
        $staff = Staff::findOrFail($id);

        $query = StaffTimeOff::where('staff_id', $staff->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('start_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('end_date', '<=', $request->to);
        }

        $timeOffs = $query->orderBy('start_date')->get();

        return response()->json(['success' => true, 'data' => $timeOffs]);
    }

    /**
     * POST /admin/api/staff/{id}/time-off
     * Body: { "start_date":"2026-03-01","end_date":"2026-03-03","all_day":true,"reason":"Vacation" }
     */
    public function storeTimeOff(Request $request, int $id): JsonResponse
    {
        $staff = Staff::findOrFail($id);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'all_day'    => 'boolean',
            'start_time' => 'nullable|date_format:H:i|required_if:all_day,false',
            'end_time'   => 'nullable|date_format:H:i|after:start_time|required_if:all_day,false',
            'reason'     => 'nullable|string|max:500',
            'status'     => 'nullable|in:pending,approved,rejected',
        ]);

        $timeOff = StaffTimeOff::create(array_merge(
            $validated,
            ['staff_id' => $staff->id, 'status' => $validated['status'] ?? 'pending']
        ));

        return response()->json(['success' => true, 'data' => $timeOff], 201);
    }

    /**
     * PUT /admin/api/staff/{id}/time-off/{timeOffId}
     */
    public function updateTimeOff(Request $request, int $id, int $timeOffId): JsonResponse
    {
        $staff   = Staff::findOrFail($id);
        $timeOff = StaffTimeOff::where('staff_id', $staff->id)->findOrFail($timeOffId);

        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date'   => 'sometimes|date|after_or_equal:start_date',
            'all_day'    => 'sometimes|boolean',
            'start_time' => 'nullable|date_format:H:i',
            'end_time'   => 'nullable|date_format:H:i',
            'reason'     => 'nullable|string|max:500',
        ]);

        $timeOff->update($validated);

        return response()->json(['success' => true, 'data' => $timeOff]);
    }

    /**
     * DELETE /admin/api/staff/{id}/time-off/{timeOffId}
     */
    public function destroyTimeOff(int $id, int $timeOffId): JsonResponse
    {
        $staff   = Staff::findOrFail($id);
        $timeOff = StaffTimeOff::where('staff_id', $staff->id)->findOrFail($timeOffId);
        $timeOff->delete();

        return response()->json(['success' => true, 'message' => __('Time-off deleted')]);
    }

    /**
     * PATCH /admin/api/staff/{id}/time-off/{timeOffId}/status
     * Body: { "status":"approved" }  (pending|approved|rejected)
     */
    public function approveTimeOff(Request $request, int $id, int $timeOffId): JsonResponse
    {
        $staff   = Staff::findOrFail($id);
        $timeOff = StaffTimeOff::where('staff_id', $staff->id)->findOrFail($timeOffId);

        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $timeOff->update(['status' => $validated['status']]);

        return response()->json(['success' => true, 'data' => $timeOff]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Commissions
    // ─────────────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/staff/{id}/commissions
     * Optional filters: ?is_paid=0|1&from=Y-m-d&to=Y-m-d
     */
    public function commissions(Request $request, int $id): JsonResponse
    {
        $staff = Staff::findOrFail($id);

        $query = StaffCommission::where('staff_id', $staff->id)
            ->with(['appointment:id,starts_at,status', 'transaction:id,amount,currency']);

        if ($request->filled('is_paid')) {
            $query->where('is_paid', (bool) $request->is_paid);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $commissions = $query->orderByDesc('created_at')->paginate(50);

        $summary = StaffCommission::where('staff_id', $staff->id)
            ->selectRaw('
                SUM(commission_amount)                                           AS total_earned,
                SUM(CASE WHEN is_paid = 1 THEN commission_amount ELSE 0 END)   AS total_paid,
                SUM(CASE WHEN is_paid = 0 THEN commission_amount ELSE 0 END)   AS total_pending,
                COUNT(*)                                                         AS total_records
            ')
            ->first();

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'data'    => $commissions,
        ]);
    }
}
