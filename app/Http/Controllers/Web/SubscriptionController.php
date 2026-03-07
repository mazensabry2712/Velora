<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\UpgradeRequestedMail;
use App\Mail\FounderAlertMail;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Show subscription details
     */
    public function index()
    {
        $subscriptionInfo = $this->subscriptionService->getSubscriptionInfo();
        $availableUpgrades = $this->subscriptionService->getAvailableUpgrades();
        $usage = $this->subscriptionService->calculateUsage();

        return view('admin.subscription.index', compact('subscriptionInfo', 'availableUpgrades', 'usage'));
    }

    /**
     * Show upgrade plans page
     */
    public function upgrade()
    {
        $currentPlan = $this->subscriptionService->getSubscriptionInfo();
        $availablePlans = $this->subscriptionService->getAvailableUpgrades();

        return view('admin.subscription.upgrade', compact('currentPlan', 'availablePlans'));
    }

    /**
     * Request upgrade
     */
    public function requestUpgrade(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:mysql.subscription_plans,id'
        ]);

        $tenantId = tenant('id');
        $planId = $request->plan_id;

        try {
            // Get plan details
            $plan = DB::connection('mysql')->table('subscription_plans')
                ->where('id', $planId)
                ->first();

            if (!$plan) {
                return back()->with('error', __('Plan not found.'));
            }

            // Create upgrade request in central DB
            $subscriptionInfo = $this->subscriptionService->getSubscriptionInfo();
            DB::connection('mysql')->table('upgrade_requests')->insert([
                'tenant_id' => $tenantId,
                'current_plan_id' => $subscriptionInfo['plan_id'] ?? null,
                'requested_plan_id' => $planId,
                'status' => 'pending',
                'requested_by_name' => auth()->user()->name,
                'requested_by_email' => auth()->user()->email,
                'message' => $request->message,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Log activity so super-admin can track it
            \App\Models\ActivityLog::log(
                'upgrade_requested',
                "Tenant requested upgrade from plan [" . ($subscriptionInfo['plan_name'] ?? 'N/A') . "] to [{$plan->name}]. Requested by: " . auth()->user()->email
            );

            // ── Notify tenant: request received confirmation ─────────────
            try {
                Mail::to(auth()->user()->email)
                    ->queue(new UpgradeRequestedMail(
                        tenantName:          auth()->user()->name,
                        currentPlanName:     $subscriptionInfo['plan_name'] ?? 'N/A',
                        requestedPlanName:   $plan->name,
                        requestedPlanPrice:  number_format($plan->price, 2),
                    ));
            } catch (\Exception $e) {
                Log::warning('Failed to send upgrade confirmation email: ' . $e->getMessage());
            }

            // ── Notify super-admin: new upgrade request pending ──────────
            try {
                $adminEmail = config('mail.founder_email');
                if ($adminEmail) {
                    Mail::to($adminEmail)
                        ->queue(new FounderAlertMail(
                            tenantId:     $tenantId,
                            businessName: $subscriptionInfo['plan_name'] ?? $tenantId,
                            ownerEmail:   auth()->user()->email,
                            triggerReason: 'New upgrade request: ' . ($subscriptionInfo['plan_name'] ?? 'N/A') . ' → ' . $plan->name,
                            trialDaysLeft: 0,
                        ));
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send admin upgrade alert: ' . $e->getMessage());
            }

            return redirect()->route('admin.subscription.index')
                ->with('success', __('Upgrade request submitted successfully. Our team will contact you shortly.'));

        } catch (\Exception $e) {
            \Log::error('Upgrade request failed: ' . $e->getMessage());
            return back()->with('error', __('Failed to submit upgrade request. Please try again.'));
        }
    }

    /**
     * Get usage API endpoint
     */
    public function getUsage()
    {
        $info = $this->subscriptionService->getSubscriptionInfo();

        return response()->json([
            'success' => true,
            'data' => $info
        ]);
    }

    /**
     * Check if action is allowed
     */
    public function checkLimit(Request $request)
    {
        $action = $request->input('action');
        $result = $this->subscriptionService->canPerformAction($action);

        return response()->json($result);
    }

    /**
     * Show billing history
     */
    public function billing()
    {
        $tenantId = tenant('id');

        try {
            $subscriptions = DB::connection('mysql')->table('tenant_subscriptions')
                ->join('subscription_plans', 'tenant_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
                ->where('tenant_subscriptions.tenant_id', $tenantId)
                ->select(
                    'tenant_subscriptions.*',
                    'subscription_plans.name as plan_name'
                )
                ->orderBy('tenant_subscriptions.created_at', 'desc')
                ->get();

            return view('admin.subscription.billing', compact('subscriptions'));
        } catch (\Exception $e) {
            \Log::error('Failed to get billing history: ' . $e->getMessage());
            return back()->with('error', __('Failed to load billing history.'));
        }
    }
}
