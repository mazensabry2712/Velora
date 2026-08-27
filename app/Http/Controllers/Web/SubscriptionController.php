<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Application\Subscription\Actions\CheckSubscriptionLimit;
use App\Application\Subscription\Actions\GetAvailableUpgrades;
use App\Application\Subscription\Actions\GetBillingHistory;
use App\Application\Subscription\Actions\GetSubscriptionOverview;
use App\Application\Subscription\Actions\GetSubscriptionUsage;
use App\Application\Subscription\Actions\RequestSubscriptionUpgrade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RequestSubscriptionUpgradeRequest;
use Illuminate\Http\Request;

final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly GetSubscriptionOverview $getOverview,
        private readonly GetAvailableUpgrades $getUpgrades,
        private readonly GetSubscriptionUsage $getUsageAction,
        private readonly GetBillingHistory $getBillingHistory,
        private readonly CheckSubscriptionLimit $checkLimitAction,
        private readonly RequestSubscriptionUpgrade $requestUpgradeAction,
    ) {}

    public function index()
    {
        return view('admin.subscription.index', [
            'subscriptionInfo' => $this->getOverview->execute(),
            'availableUpgrades' => $this->getUpgrades->execute(),
            'usage' => $this->getUsageAction->execute(),
            'invoices' => $this->getBillingHistory->execute(10),
        ]);
    }

    public function upgrade()
    {
        return view('admin.subscription.upgrade', [
            'currentPlan' => $this->getOverview->execute(),
            'availablePlans' => $this->getUpgrades->execute(),
        ]);
    }

    public function requestUpgrade(RequestSubscriptionUpgradeRequest $request)
    {
        $user = $request->user();

        $this->requestUpgradeAction->execute(
            (int) $request->validated('plan_id'),
            (string) tenant('id'),
            (string) $user->name,
            (string) $user->email,
            $request->validated('message'),
        );

        return redirect()->route('admin.subscription.index')
            ->with('success', __('Upgrade request submitted successfully. Our team will contact you shortly.'));
    }

    public function getUsage()
    {
        return response()->json([
            'success' => true,
            'data' => $this->getUsageAction->execute(),
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
