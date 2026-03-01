<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UpgradeRequest;
use App\Models\TenantSubscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\UpgradeApprovedMail;
use App\Mail\UpgradeRejectedMail;

class SuperAdminController extends Controller
{
    /**
     * Show all upgrade requests
     */
    public function upgradeRequests()
    {
        $requests = UpgradeRequest::with(['currentPlan', 'requestedPlan'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('super-admin.upgrade-requests.index', compact('requests'));
    }

    /**
     * Show single upgrade request details
     */
    public function showUpgradeRequest($id)
    {
        $request = UpgradeRequest::with(['currentPlan', 'requestedPlan'])
            ->findOrFail($id);

        // Get tenant info
        $tenant = Tenant::find($request->tenant_id);

        // Get current subscription (active or trial)
        $subscription = TenantSubscription::where('tenant_id', $request->tenant_id)
            ->whereIn('status', ['active', 'trial'])
            ->latest()
            ->first();

        // Get usage stats from tenant database
        $usage = $this->getTenantUsage($request->tenant_id);

        return view('super-admin.upgrade-requests.show', compact('request', 'tenant', 'subscription', 'usage'));
    }

    /**
     * Approve upgrade request
     */
    public function approveUpgrade(Request $request, $id)
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string',
            'start_date' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $upgradeRequest = UpgradeRequest::findOrFail($id);

            // Update request status
            $upgradeRequest->update([
                'status' => 'approved',
                'admin_notes' => $validated['admin_notes'] ?? null,
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);

            // Get current subscription (active or trial)
            $currentSubscription = TenantSubscription::where('tenant_id', $upgradeRequest->tenant_id)
                ->whereIn('status', ['active', 'trial'])
                ->latest()
                ->first();

            if ($currentSubscription) {
                // Expire current subscription
                $currentSubscription->update([
                    'status' => 'expired',
                    'ends_at' => now(),
                    'trial_ends_at' => $currentSubscription->status === 'trial' ? now() : $currentSubscription->trial_ends_at,
                ]);
            }

            // Create new subscription with requested plan
            $startDate = isset($validated['start_date']) ? \Carbon\Carbon::parse($validated['start_date']) : now();
            $newPlan = SubscriptionPlan::find($upgradeRequest->requested_plan_id);

            // Use billing_cycle to calculate proper end date (NOT trial_days)
            $endDate = match($newPlan->billing_cycle ?? 'monthly') {
                'yearly'  => $startDate->copy()->addYear(),
                'monthly' => $startDate->copy()->addMonth(),
                'weekly'  => $startDate->copy()->addWeek(),
                default   => $startDate->copy()->addMonth(),
            };

            $newSubscription = TenantSubscription::create([
                'tenant_id' => $upgradeRequest->tenant_id,
                'subscription_plan_id' => $upgradeRequest->requested_plan_id,
                'status' => 'active',
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'trial_ends_at' => $newPlan->trial_days ? $startDate->copy()->addDays($newPlan->trial_days) : null,
            ]);

            DB::commit();

            // Send email notification to tenant
            try {
                Mail::to($upgradeRequest->requested_by_email)
                    ->send(new UpgradeApprovedMail($upgradeRequest->fresh(['currentPlan', 'requestedPlan']), $newSubscription));
            } catch (\Exception $e) {
                Log::warning('Failed to send upgrade approval email: ' . $e->getMessage());
            }

            return redirect()
                ->route('super-admin.upgrade-requests')
                ->with('success', 'Upgrade request approved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to approve request: ' . $e->getMessage());
        }
    }

    /**
     * Reject upgrade request
     */
    public function rejectUpgrade(Request $request, $id)
    {
        $validated = $request->validate([
            'admin_notes' => 'required|string',
        ]);

        try {
            $upgradeRequest = UpgradeRequest::findOrFail($id);

            $upgradeRequest->update([
                'status' => 'rejected',
                'admin_notes' => $validated['admin_notes'],
                'processed_at' => now(),
                'processed_by' => auth()->id(),
            ]);

            // Send email notification to tenant
            try {
                Mail::to($upgradeRequest->requested_by_email)
                    ->send(new UpgradeRejectedMail($upgradeRequest->fresh(['currentPlan', 'requestedPlan'])));
            } catch (\Exception $e) {
                Log::warning('Failed to send upgrade rejection email: ' . $e->getMessage());
            }

            return redirect()
                ->route('super-admin.upgrade-requests')
                ->with('success', 'Upgrade request rejected.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject request: ' . $e->getMessage());
        }
    }

    /**
     * Get tenant usage statistics
     */
    private function getTenantUsage($tenantId)
    {
        try {
            // Switch to tenant database
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                return null;
            }

            tenancy()->initialize($tenant);

            $usage = [
                'total_users' => DB::table('users')->count(),
                'total_appointments' => DB::table('appointments')->count(),
                'appointments_this_month' => DB::table('appointments')
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count(),
            ];

            tenancy()->end();

            return $usage;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Show dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => TenantSubscription::where('status', 'active')->distinct('tenant_id')->count(),
            'trial_tenants' => TenantSubscription::where('status', 'trial')->distinct('tenant_id')->count(),
            'inactive_tenants' => Tenant::whereDoesntHave('subscriptions', function($q) {
                $q->whereIn('status', ['active', 'trial']);
            })->count(),
            'tenants_this_month' => Tenant::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'pending_upgrade_requests' => \App\Models\UpgradeRequest::where('status', 'pending')->count(),
            'recent_tenants' => Tenant::with('subscriptions')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($tenant) {
                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name ?? 'N/A',
                        'subdomain' => $tenant->id,
                        'is_active' => $tenant->subscriptions->whereIn('status', ['active', 'trial'])->count() > 0,
                        'created_at' => $tenant->created_at->toISOString(),
                    ];
                }),
        ];

        return view('super-admin.dashboard', compact('stats'));
    }

    /**
     * Get dashboard data via API
     */
    public function getDashboardData()
    {
        $data = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => TenantSubscription::where('status', 'active')->distinct('tenant_id')->count(),
            'trial_tenants' => TenantSubscription::where('status', 'trial')->distinct('tenant_id')->count(),
            'inactive_tenants' => Tenant::whereDoesntHave('subscriptions', function($q) {
                $q->whereIn('status', ['active', 'trial']);
            })->count(),
            'tenants_this_month' => Tenant::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'pending_upgrade_requests' => \App\Models\UpgradeRequest::where('status', 'pending')->count(),
            'recent_tenants' => Tenant::with('subscriptions')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($tenant) {
                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name ?? 'N/A',
                        'subdomain' => $tenant->id,
                        'is_active' => $tenant->subscriptions->whereIn('status', ['active', 'trial'])->count() > 0,
                        'created_at' => $tenant->created_at->toISOString(),
                    ];
                }),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
