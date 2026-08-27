<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Application\Subscription\Actions\CheckSubscriptionLimit;
use App\Application\Subscription\Actions\GetAvailableUpgrades;
use App\Application\Subscription\Actions\GetBillingHistory;
use App\Application\Subscription\Actions\GetSubscriptionOverview;
use App\Application\Subscription\Actions\GetSubscriptionUsage;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Mail\FounderAlertMail;
use App\Mail\UpgradeRequestedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly GetSubscriptionOverview $getOverview,
        private readonly GetAvailableUpgrades $getUpgrades,
        private readonly GetSubscriptionUsage $getUsageAction,
        private readonly GetBillingHistory $getBillingHistory,
        private readonly CheckSubscriptionLimit $checkLimitAction,
    ) {}

    public function index()
    {
        $subscriptionInfo = $this->getOverview->execute();
        $availableUpgrades = $this->getUpgrades->execute();
        $usage = $this->getUsageAction->execute();
        $invoices = $this->getBillingHistory->execute(10);

        return view('admin.subscription.index', compact(
            'subscriptionInfo',
            'availableUpgrades',
            'usage',
            'invoices'
        ));
    }

    public function upgrade()
    {
        $currentPlan = $this->getOverview->execute();
        $availablePlans = $this->getUpgrades->execute();

        return view('admin.subscription.upgrade', compact('currentPlan', 'availablePlans'));
    }

    public function requestUpgrade(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:mysql.subscription_plans,id',
        ]);

        $tenantId = tenant('id');
        $planId = (int) $validated['plan_id'];

        try {
            $plan = DB::connection('mysql')->table('subscription_plans')
                ->where('id', $planId)
                ->where('is_active', true)
                ->first();

            if (!$plan) {
                return back()->with('error', __('Plan not found.'));
            }

            $subscriptionInfo = $this->getOverview->execute() ?? [];
            DB::connection('mysql')->table('upgrade_requests')->insert([
                'tenant_id' => $tenantId,
                'current_plan_id' => $subscriptionInfo['plan_id'] ?? null,
                'requested_plan_id' => $planId,
                'status' => 'pending',
                'requested_by_name' => auth()->user()->name,
                'requested_by_email' => auth()->user()->email,
                'message' => $request->input('message'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            ActivityLog::log(
                'upgrade_requested',
                "Tenant requested upgrade from plan [" . ($subscriptionInfo['plan_name'] ?? 'N/A') . "] to [{$plan->name}]. Requested by: " . auth()->user()->email
            );

            try {
                Mail::to(auth()->user()->email)->queue(new UpgradeRequestedMail(
                    tenantName: auth()->user()->name,
                    currentPlanName: $subscriptionInfo['plan_name'] ?? 'N/A',
                    requestedPlanName: $plan->name,
                    requestedPlanPrice: number_format($plan->price, 2),
                ));
            } catch (\Throwable $e) {
                Log::warning('Failed to send upgrade confirmation email: ' . $e->getMessage());
            }

            try {
                $adminEmail = config('mail.founder_email');
                if ($adminEmail) {
                    Mail::to($adminEmail)->queue(new FounderAlertMail(
                        tenantId: $tenantId,
                        businessName: $subscriptionInfo['plan_name'] ?? $tenantId,
                        ownerEmail: auth()->user()->email,
                        triggerReason: 'New upgrade request: ' . ($subscriptionInfo['plan_name'] ?? 'N/A') . ' → ' . $plan->name,
                        trialDaysLeft: 0,
                    ));
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send admin upgrade alert: ' . $e->getMessage());
            }

            return redirect()->route('admin.subscription.index')
                ->with('success', __('Upgrade request submitted successfully. Our team will contact you shortly.'));
        } catch (\Throwable $e) {
            Log::error('Upgrade request failed: ' . $e->getMessage());
            return back()->with('error', __('Failed to submit upgrade request. Please try again.'));
        }
    }

    public function getUsage()
    {
        return response()->json([
            'success' => true,
            'data' => $this->getOverview->execute(),
        ]);
    }

    public function checkLimit(Request $request)
    {
        return response()->json(
            $this->checkLimitAction->execute((string) $request->input('action', ''))
        );
    }

    public function billing()
    {
        return view('admin.subscription.billing', [
            'subscriptions' => $this->getBillingHistory->execute(100),
        ]);
    }
}
