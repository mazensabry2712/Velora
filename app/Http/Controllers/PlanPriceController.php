<?php

namespace App\Http\Controllers;

use App\Models\PlanPrice;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class PlanPriceController extends Controller
{
    /**
     * List all geo prices for a subscription plan.
     */
    public function index(SubscriptionPlan $plan)
    {
        $prices = $plan->prices()->orderBy('is_default', 'desc')->orderBy('country_code')->paginate(50);
        return view('super-admin.subscription-plans.prices', compact('plan', 'prices'));
    }

    public function store(Request $request, SubscriptionPlan $plan)
    {
        $data = $request->validate([
            'country_code'   => 'nullable|size:2|alpha|uppercase',
            'currency'       => 'required|size:3|alpha|uppercase',
            'amount'         => 'required|numeric|min:0',
            'stripe_price_id'=> 'nullable|string|max:100',
            'is_default'     => 'boolean',
            'is_active'      => 'boolean',
        ]);

        // Only one default per plan
        if ($request->boolean('is_default')) {
            $plan->prices()->where('is_default', true)->update(['is_default' => false]);
        }

        $plan->prices()->create($data);

        return redirect()->route('super-admin.plan-prices.index', $plan)
            ->with('success', 'Price added.');
    }

    public function update(Request $request, SubscriptionPlan $plan, PlanPrice $planPrice)
    {
        $data = $request->validate([
            'country_code'    => 'nullable|size:2|alpha|uppercase',
            'currency'        => 'required|size:3|alpha|uppercase',
            'amount'          => 'required|numeric|min:0',
            'stripe_price_id' => 'nullable|string|max:100',
            'is_default'      => 'boolean',
            'is_active'       => 'boolean',
        ]);

        if ($request->boolean('is_default') && !$planPrice->is_default) {
            $plan->prices()->where('is_default', true)->update(['is_default' => false]);
        }

        $planPrice->update($data);

        return redirect()->route('super-admin.plan-prices.index', $plan)
            ->with('success', 'Price updated.');
    }

    public function destroy(SubscriptionPlan $plan, PlanPrice $planPrice)
    {
        $planPrice->delete();

        return redirect()->route('super-admin.plan-prices.index', $plan)
            ->with('success', 'Price deleted.');
    }
}
