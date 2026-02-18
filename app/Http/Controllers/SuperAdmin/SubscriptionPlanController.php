<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of subscription plans.
     */
    public function index()
    {
        $plans = SubscriptionPlan::withCount(['subscriptions as active_subscriptions' => function ($query) {
            $query->where('status', 'active');
        }])->orderBy('price')->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly',
            'max_users' => 'nullable|integer|min:1',
            'max_appointments' => 'nullable|integer|min:1',
            'storage_limit' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'trial_days' => 'nullable|integer|min:0',
            'is_popular' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = true;

        $plan = SubscriptionPlan::create($validated);

        ActivityLog::log('created', "Created subscription plan: {$plan->name}", SubscriptionPlan::class, $plan->id);

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan created successfully',
            'data' => $plan
        ], 201);
    }

    /**
     * Display the specified plan.
     */
    public function show(string $id)
    {
        $plan = SubscriptionPlan::withCount(['subscriptions as active_subscriptions' => function ($query) {
            $query->where('status', 'active');
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $plan
        ]);
    }

    /**
     * Update the specified plan.
     */
    public function update(Request $request, string $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'billing_cycle' => 'sometimes|in:monthly,yearly',
            'max_users' => 'nullable|integer|min:1',
            'max_appointments' => 'nullable|integer|min:1',
            'storage_limit' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'trial_days' => 'nullable|integer|min:0',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $plan->update($validated);

        ActivityLog::log('updated', "Updated subscription plan: {$plan->name}", SubscriptionPlan::class, $plan->id);

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan updated successfully',
            'data' => $plan->fresh()
        ]);
    }

    /**
     * Remove the specified plan.
     */
    public function destroy(string $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);

        // Check if plan has active subscriptions
        $activeCount = $plan->subscriptions()->where('status', 'active')->count();

        if ($activeCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete plan with {$activeCount} active subscriptions"
            ], 422);
        }

        ActivityLog::log('deleted', "Deleted subscription plan: {$plan->name}", SubscriptionPlan::class, $plan->id);

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan deleted successfully'
        ]);
    }

    /**
     * Toggle plan active status
     */
    public function toggleStatus(string $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        $plan->is_active = !$plan->is_active;
        $plan->save();

        ActivityLog::log('updated', "Toggled status for plan: {$plan->name} to " . ($plan->is_active ? 'active' : 'inactive'), SubscriptionPlan::class, $plan->id);

        return response()->json([
            'success' => true,
            'message' => 'Plan status updated successfully',
            'data' => [
                'id' => $plan->id,
                'is_active' => $plan->is_active
            ]
        ]);
    }
}
