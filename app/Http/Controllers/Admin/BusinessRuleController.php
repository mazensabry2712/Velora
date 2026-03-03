<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * BusinessRuleController — CRUD for the tenant's business rules table.
 *
 * Routes (all under admin/api, Admin Tenant role only):
 *   GET    /business-rules
 *   GET    /business-rules/{key}
 *   PUT    /business-rules           (bulk upsert)
 *   DELETE /business-rules/{key}
 */
class BusinessRuleController extends Controller
{
    /**
     * GET /admin/api/business-rules
     * List all rules.
     */
    public function index(): JsonResponse
    {
        $rules = BusinessRule::orderBy('key')->get();

        return response()->json(['success' => true, 'data' => $rules]);
    }

    /**
     * GET /admin/api/business-rules/{key}
     * Return a single rule by its key.
     */
    public function show(string $key): JsonResponse
    {
        $rule = BusinessRule::where('key', $key)->first();

        if (! $rule) {
            return response()->json(['success' => false, 'message' => __('Rule not found')], 404);
        }

        return response()->json(['success' => true, 'data' => $rule]);
    }

    /**
     * PUT /admin/api/business-rules
     * Bulk-upsert one or many rules at once.
     *
     * Body: { "rules": [ { "key":"max_advance_booking_days", "value":"30", "type":"integer", "description":"...", "is_active":true }, … ] }
     * OR shorthand: { "max_advance_booking_days": 30, "auto_confirm_bookings": true }
     */
    public function update(Request $request): JsonResponse
    {
        // Accept either forma: { rules:[…] } or flat { key:value } map
        if ($request->has('rules')) {
            $validated = $request->validate([
                'rules'               => 'required|array|min:1',
                'rules.*.key'         => 'required|string|max:100',
                'rules.*.value'       => 'required',
                'rules.*.type'        => 'nullable|in:string,integer,float,boolean',
                'rules.*.description' => 'nullable|string|max:500',
                'rules.*.is_active'   => 'nullable|boolean',
            ]);

            $upserted = [];
            foreach ($validated['rules'] as $r) {
                $upserted[] = BusinessRule::updateOrCreate(
                    ['key' => $r['key']],
                    [
                        'value'       => (string) $r['value'],
                        'type'        => $r['type']        ?? 'string',
                        'description' => $r['description'] ?? null,
                        'is_active'   => $r['is_active']   ?? true,
                    ]
                );
            }
        } else {
            // Flat map: each key→value in request body
            $upserted = [];
            foreach ($request->except(['_method', '_token']) as $key => $value) {
                if (! is_string($key) || strlen($key) > 100) {
                    continue;
                }
                $upserted[] = BusinessRule::updateOrCreate(
                    ['key' => $key],
                    ['value' => (string) $value, 'is_active' => true]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('Business rules updated'),
            'data'    => $upserted,
        ]);
    }

    /**
     * DELETE /admin/api/business-rules/{key}
     * Hard-delete a rule by key.
     */
    public function destroy(string $key): JsonResponse
    {
        $deleted = BusinessRule::where('key', $key)->delete();

        if (! $deleted) {
            return response()->json(['success' => false, 'message' => __('Rule not found')], 404);
        }

        return response()->json(['success' => true, 'message' => __('Rule deleted')]);
    }
}
