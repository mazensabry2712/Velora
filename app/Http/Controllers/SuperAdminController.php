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

        // Get current subscription
        $subscription = TenantSubscription::where('tenant_id', $request->tenant_id)
            ->where('status', 'active')
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

            // Get current subscription
            $currentSubscription = TenantSubscription::where('tenant_id', $upgradeRequest->tenant_id)
                ->where('status', 'active')
                ->first();

            if ($currentSubscription) {
                // Expire current subscription
                $currentSubscription->update([
                    'status' => 'expired',
                    'ends_at' => now(),
                ]);
            }

            // Create new subscription with requested plan
            $startDate = $validated['start_date'] ?? now();
            $newPlan = SubscriptionPlan::find($upgradeRequest->requested_plan_id);

            $newSubscription = TenantSubscription::create([
                'tenant_id' => $upgradeRequest->tenant_id,
                'plan_id' => $upgradeRequest->requested_plan_id,
                'status' => 'active',
                'starts_at' => $startDate,
                'ends_at' => now()->addDays($newPlan->trial_days ?? 30),
                'trial_ends_at' => $newPlan->trial_days ? now()->addDays($newPlan->trial_days) : null,
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
}
