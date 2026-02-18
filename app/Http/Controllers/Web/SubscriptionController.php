<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\DB;

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
            DB::connection('mysql')->table('upgrade_requests')->insert([
                'tenant_id' => $tenantId,
                'current_plan_id' => $this->subscriptionService->getSubscriptionInfo()['plan_id'],
                'requested_plan_id' => $planId,
                'status' => 'pending',
                'requested_by' => auth()->user()->name,
                'requested_by_email' => auth()->user()->email,
                'message' => $request->message,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Send notification to Super Admin (implement email notification later)

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
