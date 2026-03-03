<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    /**
     * GET /admin/api/service-categories
     * List all categories (with services count).
     */
    public function index(): JsonResponse
    {
        $categories = ServiceCategory::withCount('services')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    /**
     * GET /admin/api/service-categories/{id}
     */
    public function show(int $id): JsonResponse
    {
        $category = ServiceCategory::withCount('services')
            ->with('services:id,category_id,name,is_active')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $category,
        ]);
    }

    /**
     * POST /admin/api/service-categories
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'required|array',
            'name.en'    => 'required|string|max:100',
            'icon'       => 'nullable|string|max:50',
            'color'      => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        try {
            $slug = Str::slug($request->input('name.en'));

            // Ensure slug uniqueness
            $originalSlug = $slug;
            $counter = 1;
            while (ServiceCategory::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            $category = ServiceCategory::create([
                'name'       => $request->input('name'),
                'slug'       => $slug,
                'icon'       => $request->input('icon'),
                'color'      => $request->input('color', '#6366f1'),
                'sort_order' => $request->input('sort_order', 0),
                'is_active'  => $request->input('is_active', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Category created successfully.'),
                'data'    => $category,
            ], 201);
        } catch (\Exception $e) {
            Log::error('ServiceCategoryController@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Failed to create category.')], 500);
        }
    }

    /**
     * PUT /admin/api/service-categories/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = ServiceCategory::findOrFail($id);

        $request->validate([
            'name'       => 'sometimes|array',
            'name.en'    => 'required_with:name|string|max:100',
            'icon'       => 'nullable|string|max:50',
            'color'      => 'nullable|string|max:20',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        try {
            $data = $request->only(['name', 'icon', 'color', 'sort_order', 'is_active']);

            // Regenerate slug if name changed
            if ($request->has('name') && isset($request->name['en'])) {
                $slug         = Str::slug($request->name['en']);
                $originalSlug = $slug;
                $counter      = 1;
                while (ServiceCategory::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }
                $data['slug'] = $slug;
            }

            $category->update($data);

            return response()->json([
                'success' => true,
                'message' => __('Category updated successfully.'),
                'data'    => $category->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('ServiceCategoryController@update: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Failed to update category.')], 500);
        }
    }

    /**
     * DELETE /admin/api/service-categories/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $category = ServiceCategory::withCount('services')->findOrFail($id);

        if ($category->services_count > 0) {
            return response()->json([
                'success' => false,
                'message' => __('Cannot delete category that has services. Move or delete the services first.'),
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => __('Category deleted successfully.'),
        ]);
    }

    /**
     * PATCH /admin/api/service-categories/{id}/toggle
     * Toggle is_active status.
     */
    public function toggle(int $id): JsonResponse
    {
        $category = ServiceCategory::findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);

        return response()->json([
            'success'   => true,
            'message'   => $category->is_active ? __('Category activated.') : __('Category deactivated.'),
            'is_active' => $category->is_active,
        ]);
    }

    /**
     * POST /admin/api/service-categories/reorder
     * Update sort_order for multiple categories at once.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order'                => 'required|array|min:1',
            'order.*.id'           => 'required|integer|exists:service_categories,id',
            'order.*.sort_order'   => 'required|integer|min:0',
        ]);

        foreach ($request->input('order') as $item) {
            ServiceCategory::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => __('Order saved successfully.'),
        ]);
    }
}
