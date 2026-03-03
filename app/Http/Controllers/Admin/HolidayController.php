<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HolidayController — CRUD for business holidays.
 *
 * Holidays are automatically respected by SlotEngine:
 * - applies_to_all = true  → blocks all staff on that date
 * - applies_to_all = false → only blocks specific staff (via holiday_staff pivot)
 */
class HolidayController extends Controller
{
    /**
     * GET /admin/api/holidays
     * List all holidays (paginated, newest first).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Holiday::query();

        if ($request->filled('year')) {
            $query->whereYear('date', $request->integer('year'));
        }

        $holidays = $query->orderBy('date', 'asc')->paginate(50);

        return response()->json([
            'success' => true,
            'data'    => $holidays->items(),
            'meta'    => [
                'total'        => $holidays->total(),
                'current_page' => $holidays->currentPage(),
                'last_page'    => $holidays->lastPage(),
            ],
        ]);
    }

    /**
     * POST /admin/api/holidays
     * Create a new holiday.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date'          => 'required|date',
            'name'          => 'required',
            'name_en'       => 'required_without:name|string|max:100',
            'name_ar'       => 'nullable|string|max:100',
            'applies_to_all' => 'boolean',
        ]);

        // Support both nested (`name.en`) and flat (`name_en`) input
        if (is_array($request->input('name'))) {
            $nameJson = $request->input('name');
        } else {
            $nameJson = ['en' => $data['name_en'] ?? $data['name'], 'ar' => $data['name_ar'] ?? $data['name_en'] ?? $data['name']];
        }

        // Prevent duplicate holidays on the same date
        $exists = Holiday::whereDate('date', $data['date'])->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => __('A holiday already exists on this date.')], 422);
        }

        $holiday = Holiday::create([
            'date'           => $data['date'],
            'name'           => $nameJson,
            'applies_to_all' => $data['applies_to_all'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Holiday created.'),
            'data'    => $holiday,
        ], 201);
    }

    /**
     * PUT /admin/api/holidays/{id}
     * Update an existing holiday.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);

        $data = $request->validate([
            'date'           => 'sometimes|date',
            'name'           => 'sometimes',
            'name_en'        => 'sometimes|string|max:100',
            'name_ar'        => 'nullable|string|max:100',
            'applies_to_all' => 'sometimes|boolean',
        ]);

        if ($request->filled('date') && $request->input('date') !== $holiday->date->toDateString()) {
            $conflict = Holiday::whereDate('date', $data['date'])->where('id', '!=', $id)->exists();
            if ($conflict) {
                return response()->json(['success' => false, 'message' => __('Another holiday already exists on this date.')], 422);
            }
        }

        $updates = [];

        if (isset($data['date'])) {
            $updates['date'] = $data['date'];
        }

        if ($request->has('name') && is_array($request->input('name'))) {
            $updates['name'] = $request->input('name');
        } elseif ($request->filled('name_en')) {
            $updates['name'] = ['en' => $data['name_en'], 'ar' => $data['name_ar'] ?? $data['name_en']];
        }

        if (isset($data['applies_to_all'])) {
            $updates['applies_to_all'] = $data['applies_to_all'];
        }

        $holiday->update($updates);

        return response()->json([
            'success' => true,
            'message' => __('Holiday updated.'),
            'data'    => $holiday->fresh(),
        ]);
    }

    /**
     * DELETE /admin/api/holidays/{id}
     * Delete a holiday.
     */
    public function destroy(int $id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return response()->json(['success' => true, 'message' => __('Holiday deleted.')]);
    }

    /**
     * GET /admin/api/holidays/upcoming
     * List upcoming holidays (next 90 days).
     */
    public function upcoming(): JsonResponse
    {
        $holidays = Holiday::where('date', '>=', today())
            ->where('date', '<=', today()->addDays(90))
            ->orderBy('date')
            ->get();

        return response()->json(['success' => true, 'data' => $holidays]);
    }
}
