<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Customer\Actions\CreateCustomer;
use App\Application\Customer\Actions\DeleteCustomer;
use App\Application\Customer\Actions\SetCustomerBlockedState;
use App\Application\Customer\Actions\UpdateCustomer;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Customer Management (V2 — Customer model, not User model)
 */
final class CustomerController extends Controller
{
    public function __construct(
        private readonly CreateCustomer $createCustomer,
        private readonly UpdateCustomer $updateCustomer,
        private readonly DeleteCustomer $deleteCustomer,
        private readonly SetCustomerBlockedState $setBlockedState,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Customer::withCount('appointments');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

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

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:customers,email',
            'phone' => 'nullable|string|max:30',
            'phone_country' => 'nullable|string|max:10',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:64',
            'notes' => 'nullable|string|max:2000',
            'tags' => 'nullable|array',
            'acquisition_source' => 'nullable|string|max:100',
            'referral_code' => 'nullable|string|max:50',
            'gdpr_consent' => 'nullable|boolean',
        ]);

        if ($data['gdpr_consent'] ?? false) {
            $data['gdpr_consent_at'] = now();
            $data['gdpr_consent_ip'] = $request->ip();
        }

        return response()->json(['success' => true, 'data' => $this->createCustomer->execute($data)], 201);
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::withCount('appointments')->findOrFail($id);

        $stats = [
            'total_appointments' => $customer->appointments()->count(),
            'completed' => $customer->appointments()->where('status', 'completed')->count(),
            'cancelled' => $customer->appointments()->where('status', 'cancelled')->count(),
            'no_show' => $customer->appointments()->where('status', 'no_show')->count(),
            'avg_rating' => round((float) $customer->appointments()->whereNotNull('rating')->avg('rating'), 1),
            'total_spent' => $customer->total_spent,
            'last_visit_at' => $customer->last_visit_at,
            'ltv_tier' => $customer->ltv_tier,
        ];

        return response()->json(['data' => $customer, 'stats' => $stats]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $data = $request->validate([
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => "nullable|email|unique:customers,email,{$id}",
            'phone' => 'nullable|string|max:30',
            'phone_country' => 'nullable|string|max:10',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:64',
            'notes' => 'nullable|string|max:2000',
            'tags' => 'nullable|array',
            'acquisition_source' => 'nullable|string|max:100',
        ]);

        return response()->json(['success' => true, 'data' => $this->updateCustomer->execute($customer, $data)]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteCustomer->execute(Customer::findOrFail($id));

        return response()->json(['success' => true, 'message' => 'Customer deleted.']);
    }

    public function appointments(Request $request, int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $appointments = $customer->appointments()
            ->with(['service:id,name,price', 'staff:id,first_name,last_name'])
            ->orderByDesc('starts_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json($appointments);
    }

    public function block(Request $request, int $id): JsonResponse
    {
        $customer = $this->setBlockedState->execute(
            Customer::findOrFail($id),
            true,
            $request->input('reason')
        );

        return response()->json(['success' => true, 'message' => 'Customer blocked.', 'data' => $customer]);
    }

    public function unblock(int $id): JsonResponse
    {
        $customer = $this->setBlockedState->execute(Customer::findOrFail($id), false);

        return response()->json(['success' => true, 'message' => 'Customer unblocked.', 'data' => $customer]);
    }
}
