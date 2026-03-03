<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReminderRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ReminderRuleController — CRUD for tenant reminder rules.
 *
 * Routes (all under admin/api, Admin Tenant role only):
 *   GET    /reminder-rules
 *   POST   /reminder-rules
 *   GET    /reminder-rules/{id}
 *   PUT    /reminder-rules/{id}
 *   DELETE /reminder-rules/{id}
 *   PATCH  /reminder-rules/{id}/toggle
 *   POST   /reminder-rules/reorder
 */
class ReminderRuleController extends Controller
{
    /**
     * GET /admin/api/reminder-rules
     */
    public function index(): JsonResponse
    {
        $rules = ReminderRule::orderBy('sort_order')->orderBy('id')->get();

        return response()->json(['success' => true, 'data' => $rules]);
    }

    /**
     * POST /admin/api/reminder-rules
     * Body: { "name":{"en":"Day before"}, "trigger_type":"before_appointment",
     *          "trigger_minutes":1440, "channel":"email",
     *          "send_to_customer":true, "send_to_staff":false }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRule($request);

        $rule = ReminderRule::create(array_merge($validated, [
            'sort_order' => ReminderRule::max('sort_order') + 1,
            'is_active'  => $validated['is_active'] ?? true,
        ]));

        return response()->json(['success' => true, 'data' => $rule], 201);
    }

    /**
     * GET /admin/api/reminder-rules/{id}
     */
    public function show(int $id): JsonResponse
    {
        $rule = ReminderRule::findOrFail($id);

        return response()->json(['success' => true, 'data' => $rule]);
    }

    /**
     * PUT /admin/api/reminder-rules/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $rule      = ReminderRule::findOrFail($id);
        $validated = $this->validateRule($request, update: true);

        $rule->update($validated);

        return response()->json(['success' => true, 'data' => $rule->fresh()]);
    }

    /**
     * DELETE /admin/api/reminder-rules/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        ReminderRule::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => __('Reminder rule deleted')]);
    }

    /**
     * PATCH /admin/api/reminder-rules/{id}/toggle
     * Flip is_active.
     */
    public function toggle(int $id): JsonResponse
    {
        $rule = ReminderRule::findOrFail($id);
        $rule->update(['is_active' => ! $rule->is_active]);

        return response()->json(['success' => true, 'data' => $rule]);
    }

    /**
     * POST /admin/api/reminder-rules/reorder
     * Body: { "ids": [3, 1, 2] }  — ordered list of IDs
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array']);

        foreach ($request->ids as $position => $id) {
            ReminderRule::where('id', $id)->update(['sort_order' => $position + 1]);
        }

        return response()->json(['success' => true, 'message' => __('Order saved')]);
    }

    // ─────────────────────────────────────────────────────────────────────

    private function validateRule(Request $request, bool $update = false): array
    {
        $required = $update ? 'sometimes' : 'required';

        return $request->validate([
            'name'             => "{$required}|array",
            'name.en'          => 'nullable|string|max:100',
            'trigger_type'     => "{$required}|string|in:before_appointment,after_appointment,on_booking,on_cancellation",
            'trigger_minutes'  => "{$required}|integer|min:0|max:43200",
            'channel'          => "{$required}|string|in:email,sms,push",
            'template_key'     => 'nullable|string|max:100',
            'template_vars'    => 'nullable|array',
            'send_to_customer' => 'boolean',
            'send_to_staff'    => 'boolean',
            'is_active'        => 'boolean',
            'sort_order'       => 'nullable|integer|min:0',
        ]);
    }
}
