<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ResourceController — CRUD for bookable resources (rooms, chairs, equipment).
 *
 * Routes (all under admin/api, Admin Tenant role only):
 *   GET    /resources
 *   POST   /resources
 *   GET    /resources/{id}
 *   PUT    /resources/{id}
 *   DELETE /resources/{id}
 *   PATCH  /resources/{id}/toggle
 *   POST   /resources/{id}/services          attach service(s) to resource
 *   DELETE /resources/{id}/services/{serviceId}  detach
 */
class ResourceController extends Controller
{
    /**
     * GET /admin/api/resources
     * @query type     filter by type (room|chair|equipment|…)
     * @query active   1|0
     */
    public function index(Request $request): JsonResponse
    {
        $query = Resource::withCount('services');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('active')) {
            $query->where('is_active', (bool) $request->active);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('type')->orderBy('id')->get(),
        ]);
    }

    /**
     * POST /admin/api/resources
     * Body: { "name":{"en":"Room A"}, "type":"room", "quantity":1, "color":"#FF5733" }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateResource($request);

        $resource = Resource::create(array_merge($validated, ['is_active' => true]));

        return response()->json(['success' => true, 'data' => $resource], 201);
    }

    /**
     * GET /admin/api/resources/{id}
     */
    public function show(int $id): JsonResponse
    {
        $resource = Resource::with('services:id,name')->findOrFail($id);

        return response()->json(['success' => true, 'data' => $resource]);
    }

    /**
     * PUT /admin/api/resources/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $resource  = Resource::findOrFail($id);
        $validated = $this->validateResource($request, update: true);

        $resource->update($validated);

        return response()->json(['success' => true, 'data' => $resource->fresh()]);
    }

    /**
     * DELETE /admin/api/resources/{id}
     * Blocks deletion if resource is linked to active services.
     */
    public function destroy(int $id): JsonResponse
    {
        $resource = Resource::withCount('services')->findOrFail($id);

        if ($resource->services_count > 0) {
            return response()->json([
                'success' => false,
                'message' => __('Cannot delete resource linked to :count service(s). Detach them first.', [
                    'count' => $resource->services_count,
                ]),
            ], 409);
        }

        $resource->delete();

        return response()->json(['success' => true, 'message' => __('Resource deleted')]);
    }

    /**
     * PATCH /admin/api/resources/{id}/toggle
     */
    public function toggle(int $id): JsonResponse
    {
        $resource = Resource::findOrFail($id);
        $resource->update(['is_active' => ! $resource->is_active]);

        return response()->json(['success' => true, 'data' => $resource]);
    }

    /**
     * POST /admin/api/resources/{id}/services
     * Attach one or more services to a resource.
     * Body: { "service_ids": [1, 2, 3], "quantity": 1 }
     */
    public function attachServices(Request $request, int $id): JsonResponse
    {
        $resource = Resource::findOrFail($id);

        $validated = $request->validate([
            'service_ids'   => 'required|array|min:1',
            'service_ids.*' => 'integer|exists:services,id',
            'quantity'      => 'nullable|integer|min:1',
        ]);

        $pivotData = [];
        foreach ($validated['service_ids'] as $serviceId) {
            $pivotData[$serviceId] = ['quantity' => $validated['quantity'] ?? 1];
        }

        $resource->services()->syncWithoutDetaching($pivotData);

        return response()->json(['success' => true, 'data' => $resource->load('services:id,name')]);
    }

    /**
     * DELETE /admin/api/resources/{id}/services/{serviceId}
     */
    public function detachService(int $id, int $serviceId): JsonResponse
    {
        $resource = Resource::findOrFail($id);
        $resource->services()->detach($serviceId);

        return response()->json(['success' => true, 'message' => __('Service detached from resource')]);
    }

    // ─────────────────────────────────────────────────────────────────────

    private function validateResource(Request $request, bool $update = false): array
    {
        $required = $update ? 'sometimes' : 'required';

        return $request->validate([
            'name'      => "{$required}|array",
            'name.en'   => 'nullable|string|max:100',
            'type'      => "{$required}|string|max:50",
            'quantity'  => 'nullable|integer|min:1',
            'color'     => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
            'metadata'  => 'nullable|array',
        ]);
    }
}
