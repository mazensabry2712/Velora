<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Customer\Actions\CreateCustomer;
use App\Application\Customer\Actions\DeleteCustomer;
use App\Application\Customer\Actions\GetCustomer;
use App\Application\Customer\Actions\GetCustomerAppointments;
use App\Application\Customer\Actions\GetCustomerStatistics;
use App\Application\Customer\Actions\GetCustomers;
use App\Application\Customer\Actions\SetCustomerBlockedState;
use App\Application\Customer\Actions\UpdateCustomer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin Customer Management (V2 — Customer model, not User model)
 */
final class CustomerController extends Controller
{
    public function __construct(
        private readonly GetCustomers $getCustomers,
        private readonly GetCustomer $getCustomer,
        private readonly GetCustomerAppointments $getCustomerAppointments,
        private readonly GetCustomerStatistics $getCustomerStatistics,
        private readonly CreateCustomer $createCustomer,
        private readonly UpdateCustomer $updateCustomer,
        private readonly DeleteCustomer $deleteCustomer,
        private readonly SetCustomerBlockedState $setBlockedState,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->input('search'),
            'ltv_tier' => $request->input('ltv_tier'),
            'is_blocked' => $request->filled('is_blocked')
                ? filter_var($request->input('is_blocked'), FILTER_VALIDATE_BOOLEAN)
                : null,
            'tag' => $request->input('tag'),
            'acquisition_source' => $request->input('acquisition_source'),
        ];

        return response()->json(
            $this->getCustomers->execute($filters, $request->integer('per_page', 20))
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

        return response()->json([
            'success' => true,
            'data' => $this->createCustomer->execute($data),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => $this->getCustomer->execute($id),
            'stats' => $this->getCustomerStatistics->execute($id),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $customer = $this->getCustomer->execute($id);
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

        return response()->json([
            'success' => true,
            'data' => $this->updateCustomer->execute($customer, $data),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteCustomer->execute($this->getCustomer->execute($id));

        return response()->json(['success' => true, 'message' => 'Customer deleted.']);
    }

    public function appointments(Request $request, int $id): JsonResponse
    {
        return response()->json(
            $this->getCustomerAppointments->execute($id, $request->integer('per_page', 15))
        );
    }

    public function block(Request $request, int $id): JsonResponse
    {
        $customer = $this->setBlockedState->execute(
            $this->getCustomer->execute($id),
            true,
            $request->input('reason')
        );

        return response()->json(['success' => true, 'message' => 'Customer blocked.', 'data' => $customer]);
    }

    public function unblock(int $id): JsonResponse
    {
        $customer = $this->setBlockedState->execute($this->getCustomer->execute($id), false);

        return response()->json(['success' => true, 'message' => 'Customer unblocked.', 'data' => $customer]);
    }
}
