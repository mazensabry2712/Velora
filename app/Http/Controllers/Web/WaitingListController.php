<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Service;
use App\Models\WaitingList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * WaitingListController — handles public & admin waiting list operations.
 *
 * Public:
 *   POST   /api/waiting-list/join   — join the waiting list
 *   GET    /api/waiting-list/status — check status by email
 *   DELETE /api/waiting-list/{id}/cancel — cancel a waiting list entry
 *
 * Admin:
 *   GET    /admin/api/waiting-list         — list all entries
 *   POST   /admin/api/waiting-list/{id}/notify — manually notify a customer
 *   DELETE /admin/api/waiting-list/{id}    — remove an entry
 */
class WaitingListController extends Controller
{
    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * POST /api/waiting-list/join
     * A customer requests to be added to the waiting list.
     */
    public function join(Request $request): JsonResponse
    {
        if ($response = $this->rateLimitPublicRequest($request, 'join', 10)) {
            return $response;
        }

        $data = $request->validate([
            'name'               => 'required|string|max:100',
            'email'              => 'nullable|email|max:160',
            'phone'              => 'nullable|string|max:30',
            'service_id'         => 'required|integer|exists:services,id',
            'preferred_date'     => 'nullable|date|after_or_equal:today',
            'preferred_days'     => 'nullable|array',
            'preferred_days.*'   => 'integer|between:0,6',
            'preferred_time_from' => 'nullable|date_format:H:i',
            'preferred_time_to'  => 'nullable|date_format:H:i',
        ]);

        if (empty($data['email']) && empty($data['phone'])) {
            return response()->json(['success' => false, 'message' => __('Email or phone is required.')], 422);
        }

        // Find or create customer in the new customers table
        $customer = $this->findOrCreateCustomer($data);

        if (! $customer) {
            return response()->json(['success' => false, 'message' => __('Could not identify customer.')], 422);
        }

        // Check for duplicate entry
        $existing = WaitingList::where('customer_id', $customer->id)
            ->where('service_id', $data['service_id'])
            ->whereIn('status', ['waiting', 'notified'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => __('You are already on the waiting list for this service.'),
                'data'    => ['waiting_list_id' => $existing->id],
            ], 409);
        }

        $entry = WaitingList::create([
            'customer_id'        => $customer->id,
            'service_id'         => $data['service_id'],
            'staff_id'           => null,
            'preferred_date'     => $data['preferred_date'] ?? null,
            'preferred_days'     => $data['preferred_days'] ?? null,
            'preferred_time_from' => $data['preferred_time_from'] ?? null,
            'preferred_time_to'  => $data['preferred_time_to'] ?? null,
            'status'             => 'waiting',
            'expires_at'         => now()->addDays(30),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('You have been added to the waiting list. We will notify you when a slot becomes available.'),
            'data'    => [
                'waiting_list_id' => $entry->id,
                'status'         => $entry->status,
                'expires_at'     => $entry->expires_at?->toDateString(),
            ],
        ], 201);
    }

    /**
     * GET /api/waiting-list/status?email=...
     * Check waiting list status by email.
     */
    public function status(Request $request): JsonResponse
    {
        if ($response = $this->rateLimitPublicRequest($request, 'status', 30)) {
            return $response;
        }

        $request->validate(['email' => 'required|email']);

        $customer = Customer::where('email', $request->input('email'))->first();

        if (! $customer) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $entries = WaitingList::where('customer_id', $customer->id)
            ->with('service:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($e) => [
                'id'             => $e->id,
                'service'        => $e->service?->name,
                'preferred_date' => $e->preferred_date?->toDateString(),
                'status'         => $e->status,
                'notified_at'    => $e->notified_at?->toDateTimeString(),
                'expires_at'     => $e->expires_at?->toDateString(),
            ]);

        return response()->json(['success' => true, 'data' => $entries]);
    }

    /**
     * DELETE /api/waiting-list/{id}/cancel
     * Cancel a waiting list entry.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        if ($response = $this->rateLimitPublicRequest($request, 'cancel', 10)) {
            return $response;
        }

        $request->validate(['email' => 'required|email']);

        $customer = Customer::where('email', $request->input('email'))->first();

        if (! $customer) {
            return response()->json(['success' => false, 'message' => __('Not found.')], 404);
        }

        $entry = WaitingList::where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();

        if (! $entry) {
            return response()->json(['success' => false, 'message' => __('Waiting list entry not found.')], 404);
        }

        $entry->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => __('Removed from waiting list.')]);
    }

    // ── Admin API ─────────────────────────────────────────────────────────────

    /**
     * GET /admin/api/waiting-list
     * List all waiting list entries for admin.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = WaitingList::with(['customer:id,first_name,last_name,email,phone', 'service:id,name'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->integer('service_id'));
        }

        $entries = $query->paginate(30);

        $data = $entries->getCollection()->map(fn ($e) => [
            'id'             => $e->id,
            'customer_name'  => trim(($e->customer?->first_name ?? '') . ' ' . ($e->customer?->last_name ?? '')),
            'customer_email' => $e->customer?->email,
            'customer_phone' => $e->customer?->phone,
            'service'        => $e->service?->name,
            'service_id'     => $e->service_id,
            'preferred_date' => $e->preferred_date?->toDateString(),
            'preferred_days' => $e->preferred_days,
            'status'         => $e->status,
            'notified_at'    => $e->notified_at?->toDateTimeString(),
            'notification_count' => $e->notification_count,
            'expires_at'     => $e->expires_at?->toDateString(),
            'created_at'     => $e->created_at->toDateTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'total'        => $entries->total(),
                'current_page' => $entries->currentPage(),
                'last_page'    => $entries->lastPage(),
            ],
        ]);
    }

    /**
     * POST /admin/api/waiting-list/{id}/notify
     * Manually notify a waiting customer that a slot is available.
     */
    public function adminNotify(int $id): JsonResponse
    {
        $entry = WaitingList::with(['customer', 'service'])->findOrFail($id);

        if ($entry->status !== 'waiting') {
            return response()->json(['success' => false, 'message' => __('Customer is not in waiting status.')], 400);
        }

        $customer = $entry->customer;

        if (! $customer || ! $customer->email) {
            return response()->json(['success' => false, 'message' => __('Customer has no email address.')], 400);
        }

        try {
            Mail::raw(
                __('A slot is now available for :service. Please book now at your earliest convenience.', [
                    'service' => $entry->service?->name ?? 'your requested service',
                ]),
                function ($m) use ($customer) {
                    $m->to($customer->email)->subject(__('A slot is available for you!'));
                }
            );

            $entry->update([
                'status'             => 'notified',
                'notified_at'        => now(),
                'notification_count' => $entry->notification_count + 1,
            ]);

            return response()->json(['success' => true, 'message' => __('Notification sent.')]);

        } catch (\Throwable $e) {
            Log::warning("WaitingList notify failed [{$id}]: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Failed to send notification.')], 500);
        }
    }

    /**
     * DELETE /admin/api/waiting-list/{id}
     * Remove a waiting list entry (admin).
     */
    public function adminDestroy(int $id): JsonResponse
    {
        $entry = WaitingList::findOrFail($id);
        $entry->delete();

        return response()->json(['success' => true, 'message' => __('Entry removed.')]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function findOrCreateCustomer(array $data): ?Customer
    {
        // Try to find by email first
        if (! empty($data['email'])) {
            $customer = Customer::where('email', $data['email'])->first();
            if ($customer) {
                return $customer;
            }
        }

        // Try to find by phone
        if (! empty($data['phone'])) {
            $customer = Customer::where('phone', $data['phone'])->first();
            if ($customer) {
                return $customer;
            }
        }

        // Create new customer
        $nameParts  = explode(' ', trim($data['name']), 2);
        $firstName  = $nameParts[0];
        $lastName   = $nameParts[1] ?? '';

        return Customer::create([
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $data['email'] ?? null,
            'phone'      => $data['phone'] ?? null,
        ]);
    }

    private function rateLimitPublicRequest(Request $request, string $action, int $maxAttempts): ?JsonResponse
    {
        $tenantId = (string) tenant()->getTenantKey();
        $key = 'public-waiting-list:' . $action . ':' . $tenantId . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        return null;
    }
}
