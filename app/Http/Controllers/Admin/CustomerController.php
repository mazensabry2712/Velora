<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Customer Management (V2 — Customer model, not User model)
 *
 * Routes: GET/POST   /admin/api/v2/customers
 *         GET/PUT/DELETE /admin/api/v2/customers/{id}
 *         GET        /admin/api/v2/customers/{id}/appointments
 *         PATCH      /admin/api/v2/customers/{id}/block
 *         PATCH      /admin/api/v2/customers/{id}/unblock
 */
class CustomerController extends Controller
{
    /**
     * GET /admin/api/v2/customers
     * Paginated list with search + filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::withCount('appointments');

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name',  'like', "%{$search}%")
                  ->orWhere('email',      'like', "%{$search}%")
                  ->orWhere('phone',      'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('ltv_tier')) {
            $query->where('ltv_tier', $request->input('ltv_tier'));
        }

        if ($request->filled('is_blocked')) {
            $query->where('is_blocked', filter_var($request->input('is_blocked'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('tag')) {
            $query->whereJsonContains('tags', $request->input('tag'));
        }

        if ($request->filled('acquisition_source')) {
            $query->where('acquisition_source', $request->input('acquisition_source'));
        }

        $customers = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($customers);
    }

    /**
     * POST /admin/api/v2/customers
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'nullable|string|max:100',
            'email'              => 'nullable|email|unique:customers,email',
            'phone'              => 'nullable|string|max:30',
            'phone_country'      => 'nullable|string|max:10',
            'dob'                => 'nullable|date',
            'gender'             => 'nullable|in:male,female,other',
            'language'           => 'nullable|string|max:10',
            'timezone'           => 'nullable|string|max:64',
            'notes'              => 'nullable|string|max:2000',
            'tags'               => 'nullable|array',
            'acquisition_source' => 'nullable|string|max:100',
            'referral_code'      => 'nullable|string|max:50',
            'gdpr_consent'       => 'nullable|boolean',
        ]);

        if ($data['gdpr_consent'] ?? false) {
            $data['gdpr_consent_at'] = now();
            $data['gdpr_consent_ip'] = $request->ip();
        }

        $customer = Customer::create($data);

        return response()->json(['success' => true, 'data' => $customer], 201);
    }

    /**
     * GET /admin/api/v2/customers/{id}
     * Full profile with lifecycle stats.
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::withCount('appointments')->findOrFail($id);

        $stats = [
            'total_appointments' => $customer->appointments()->count(),
            'completed'          => $customer->appointments()->where('status', 'completed')->count(),
            'cancelled'          => $customer->appointments()->where('status', 'cancelled')->count(),
            'no_show'            => $customer->appointments()->where('status', 'no_show')->count(),
            'avg_rating'         => round(
                (float) $customer->appointments()->whereNotNull('rating')->avg('rating'),
                1
            ),
            'total_spent'        => $customer->total_spent,
            'last_visit_at'      => $customer->last_visit_at,
            'ltv_tier'           => $customer->ltv_tier,
        ];

        return response()->json(['data' => $customer, 'stats' => $stats]);
    }

    /**
     * PUT /admin/api/v2/customers/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $data = $request->validate([
            'first_name'         => 'sometimes|required|string|max:100',
            'last_name'          => 'nullable|string|max:100',
            'email'              => "nullable|email|unique:customers,email,{$id}",
            'phone'              => 'nullable|string|max:30',
            'phone_country'      => 'nullable|string|max:10',
            'dob'                => 'nullable|date',
            'gender'             => 'nullable|in:male,female,other',
            'language'           => 'nullable|string|max:10',
            'timezone'           => 'nullable|string|max:64',
            'notes'              => 'nullable|string|max:2000',
            'tags'               => 'nullable|array',
            'acquisition_source' => 'nullable|string|max:100',
        ]);

        $customer->update($data);

        return response()->json(['success' => true, 'data' => $customer->fresh()]);
    }

    /**
     * DELETE /admin/api/v2/customers/{id}
     * Soft-delete — preserves appointment history.
     */
    public function destroy(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return response()->json(['success' => true, 'message' => 'Customer deleted.']);
    }

    /**
     * GET /admin/api/v2/customers/{id}/appointments
     * Paginated appointment history for this customer.
     */
    public function appointments(Request $request, int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $appointments = $customer->appointments()
            ->with(['service:id,name,price', 'staff:id,first_name,last_name'])
            ->orderByDesc('starts_at')
            ->paginate($request->input('per_page', 15));

        return response()->json($appointments);
    }

    /**
     * PATCH /admin/api/v2/customers/{id}/block
     */
    public function block(Request $request, int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $reason   = $request->input('reason');

        $customer->update(['is_blocked' => true, 'block_reason' => $reason]);

        return response()->json(['success' => true, 'message' => 'Customer blocked.']);
    }

    /**
     * PATCH /admin/api/v2/customers/{id}/unblock
     */
    public function unblock(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['is_blocked' => false, 'block_reason' => null]);

        return response()->json(['success' => true, 'message' => 'Customer unblocked.']);
    }
}
