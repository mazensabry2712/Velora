<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Show booking page.
     */
    public function booking()
    {
        $settingsModel = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
        $availableLanguages = $settingsModel && $settingsModel->available_languages
            ? $settingsModel->available_languages
            : ['en', 'ar'];

        if (! is_array($availableLanguages)) {
            $availableLanguages = ['en', 'ar'];
        }

        return view('customer.booking', compact('availableLanguages'));
    }

    /**
     * Check my queue status.
     */
    public function myQueue(Request $request)
    {
        $queueNumber = $request->input('queue_number');
        return view('customer.my-queue', compact('queueNumber'));
    }

    /**
     * Render the admin customers page.
     * GET /admin/customers
     */
    public function adminPage()
    {
        return view('admin.customers.index');
    }

    /**
     * Customer management compatibility API.
     * Uses the current Customer model, whose appointments are linked through
     * appointments.customer_id_new.
     */
    public function adminIndex(Request $request)
    {
        try {
            $query = Customer::query();

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            if ($request->filled('is_vip')) {
                $isVip = filter_var($request->input('is_vip'), FILTER_VALIDATE_BOOLEAN);
                $query->where('ltv_tier', $isVip ? 'vip' : '!=vip');
            }

            $total = $query->count();
            $customers = $query->withCount('appointments')
                ->orderByDesc('created_at')
                ->paginate((int) $request->input('per_page', 20));

            $data = collect($customers->items())->map(function (Customer $customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->full_name,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'is_vip' => $customer->ltv_tier === 'vip',
                    'is_blocked' => $customer->is_blocked,
                    'appointments_count' => $customer->appointments_count,
                    'ltv_tier' => $customer->ltv_tier,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $total,
                'pages' => $customers->lastPage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('CustomerController@adminIndex: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Unable to load customers.'], 500);
        }
    }

    /**
     * Return current Customer profile with lifecycle stats.
     */
    public function adminShow($id)
    {
        try {
            $customer = Customer::findOrFail($id);

            $stats = [
                'total_appointments' => $customer->appointments()->count(),
                'completed' => $customer->appointments()->where('status', 'completed')->count(),
                'cancelled' => $customer->appointments()->where('status', 'cancelled')->count(),
                'avg_rating' => round((float) $customer->appointments()->whereNotNull('rating')->avg('rating'), 1),
            ];

            $data = [
                'id' => $customer->id,
                'name' => $customer->full_name,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'is_vip' => $customer->ltv_tier === 'vip',
                'is_blocked' => $customer->is_blocked,
                'ltv_tier' => $customer->ltv_tier,
                'total_spent' => $customer->total_spent,
                'total_visits' => $customer->total_visits,
                'last_visit_at' => $customer->last_visit_at,
            ];

            return response()->json(['success' => true, 'data' => $data, 'stats' => $stats]);
        } catch (\Throwable $e) {
            Log::error('CustomerController@adminShow: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Customer not found.'], 404);
        }
    }

    /**
     * Toggle VIP status by switching the lifecycle tier.
     */
    public function toggleVip($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $customer->update(['ltv_tier' => $customer->ltv_tier === 'vip' ? 'regular' : 'vip']);

            return response()->json([
                'success' => true,
                'is_vip' => $customer->ltv_tier === 'vip',
                'message' => $customer->ltv_tier === 'vip' ? 'Customer marked as VIP.' : 'Customer VIP status removed.',
            ]);
        } catch (\Throwable $e) {
            Log::error('CustomerController@toggleVip: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Unable to update customer.'], 500);
        }
    }

    /**
     * Return appointment history for the current Customer model.
     */
    public function getAppointments(Request $request, $id)
    {
        try {
            $customer = Customer::findOrFail($id);

            $appointments = $customer->appointments()
                ->with(['service', 'staff'])
                ->orderByDesc('starts_at')
                ->paginate((int) $request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $appointments->items(),
                'total' => $appointments->total(),
                'pages' => $appointments->lastPage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('CustomerController@getAppointments: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Unable to load appointments.'], 500);
        }
    }

    /**
     * Delete a customer record while preserving appointment history via soft delete.
     */
    public function destroy($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $customer->delete();

            return response()->json(['success' => true, 'message' => 'Customer deleted successfully.']);
        } catch (\Throwable $e) {
            Log::error('CustomerController@destroy: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Unable to delete customer.'], 500);
        }
    }
}
