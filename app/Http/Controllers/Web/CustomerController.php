<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Show booking page
     */
    public function booking()
    {
        // Get available languages from settings
        $settingsModel = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
        $availableLanguages = $settingsModel && $settingsModel->available_languages
            ? $settingsModel->available_languages
            : ['en', 'ar'];

        // Ensure it's an array
        if (!is_array($availableLanguages)) {
            $availableLanguages = ['en', 'ar'];
        }

        return view('customer.booking', compact('availableLanguages'));
    }

    /**
     * Check my queue status
     */
    public function myQueue(Request $request)
    {
        $queueNumber = $request->input('queue_number');
        return view('customer.my-queue', compact('queueNumber'));
    }

    // ────────────────────────────────────────────────────────────────────
    // Admin-facing Customer Management
    // ────────────────────────────────────────────────────────────────────

    /**
     * Render the admin customers page.
     * GET /admin/customers
     */
    public function adminPage()
    {
        return view('admin.customers.index');
    }

    /**
     * Return paginated customer list with stats (AJAX).
     * GET /admin/api/customers
     */
    public function adminIndex(Request $request)
    {
        try {
            $query = User::role('Customer');

            // Search
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // VIP filter
            if ($request->filled('is_vip')) {
                $query->where('is_vip', (bool) $request->input('is_vip'));
            }

            $total     = $query->count();
            $customers = $query->withCount('appointments')
                ->orderByDesc('created_at')
                ->paginate($request->input('per_page', 20));

            return response()->json([
                'success' => true,
                'data'    => $customers->items(),
                'total'   => $total,
                'pages'   => $customers->lastPage(),
            ]);
        } catch (\Exception $e) {
            Log::error('CustomerController@adminIndex: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ'], 500);
        }
    }

    /**
     * Return full customer profile with stats (AJAX).
     * GET /admin/api/customers/{id}
     */
    public function adminShow($id)
    {
        try {
            $customer = User::with(['appointments.service', 'appointments.staff'])->findOrFail($id);

            $stats = [
                'total_appointments' => $customer->appointments()->count(),
                'completed'          => $customer->appointments()->where('status', 'completed')->count(),
                'cancelled'          => $customer->appointments()->where('status', 'cancelled')->count(),
                'avg_rating'         => round($customer->appointments()->whereNotNull('rating')->avg('rating'), 1),
            ];

            return response()->json([
                'success'  => true,
                'data'     => $customer,
                'stats'    => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('CustomerController@adminShow: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'العميل غير موجود'], 404);
        }
    }

    /**
     * Toggle VIP status for a customer (AJAX).
     * PUT /admin/api/customers/{id}/vip
     */
    public function toggleVip($id)
    {
        try {
            $customer = User::findOrFail($id);
            $customer->update(['is_vip' => !$customer->is_vip]);

            return response()->json([
                'success' => true,
                'is_vip'  => $customer->is_vip,
                'message' => $customer->is_vip ? 'تم تعيين العميل كـ VIP' : 'تم إلغاء حالة VIP',
            ]);
        } catch (\Exception $e) {
            Log::error('CustomerController@toggleVip: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ'], 500);
        }
    }

    /**
     * Return appointment history for a customer (AJAX).
     * GET /admin/api/customers/{id}/appointments
     */
    public function getAppointments(Request $request, $id)
    {
        try {
            $query = Appointment::where('customer_id', $id)
                ->with(['service', 'staff'])
                ->orderByDesc('date');

            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            $appointments = $query->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'data'    => $appointments->items(),
                'total'   => $appointments->total(),
                'pages'   => $appointments->lastPage(),
            ]);
        } catch (\Exception $e) {
            Log::error('CustomerController@getAppointments: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ'], 500);
        }
    }

    /**
     * Delete a customer account (AJAX).
     * DELETE /admin/api/customers/{id}
     */
    public function destroy($id)
    {
        try {
            $customer = User::findOrFail($id);

            // Safety: only delete customer-role users
            if (!$customer->hasRole('Customer')) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف هذا المستخدم'], 403);
            }

            $customer->delete();

            return response()->json(['success' => true, 'message' => 'تم حذف العميل بنجاح']);
        } catch (\Exception $e) {
            Log::error('CustomerController@destroy: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ'], 500);
        }
    }
}
