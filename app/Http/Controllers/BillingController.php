<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    /**
     * Show subscription expired / upgrade page.
     * Route: GET /billing/expired  (tenant domain)
     */
    public function expired()
    {
        $tenantId = tenant('id');

        $subscription = DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->join('subscription_plans', 'tenant_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->where('tenant_subscriptions.tenant_id', $tenantId)
            ->orderByDesc('tenant_subscriptions.created_at')
            ->select(
                'tenant_subscriptions.*',
                'subscription_plans.name as plan_name',
                'subscription_plans.price',
                'subscription_plans.billing_cycle',
            )
            ->first();

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price', 'asc')
            ->get();

        $invoices = DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->where('amount_paid', '>', 0)
            ->orderByDesc('created_at')
            ->get();

        return view('billing.expired', compact('subscription', 'plans', 'invoices'));
    }

    /**
     * Show billing dashboard (inside tenant admin).
     * Route: GET /admin/subscription
     */
    public function index()
    {
        $tenantId = tenant('id');

        $subscription = DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->join('subscription_plans', 'tenant_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->where('tenant_subscriptions.tenant_id', $tenantId)
            ->orderByDesc('tenant_subscriptions.created_at')
            ->select(
                'tenant_subscriptions.*',
                'subscription_plans.name as plan_name',
                'subscription_plans.max_users',
                'subscription_plans.max_appointments',
                'subscription_plans.storage_limit',
                'subscription_plans.features',
                'subscription_plans.price',
                'subscription_plans.billing_cycle',
            )
            ->first();

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price', 'asc')
            ->get();

        $invoices = DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // Usage from tenant DB
        $usersCount        = \App\Models\User::count();
        $appointmentsCount = \App\Models\Appointment::whereMonth('created_at', now()->month)->count();

        return view('billing.index', compact(
            'subscription', 'plans', 'invoices',
            'usersCount', 'appointmentsCount'
        ));
    }

    /**
     * Create Stripe Checkout Session and redirect.
     * Route: POST /billing/checkout
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:mysql.subscription_plans,id',
        ]);

        $tenantId = tenant('id');

        // Get tenant info from central data
        $tenantData = DB::table('tenants')
            ->where('id', $tenantId)
            ->first();

        $name  = data_get(json_decode($tenantData->data ?? '{}', true), 'name', 'Tenant');
        $email = data_get(json_decode($tenantData->data ?? '{}', true), 'email', 'tenant@velora.com');

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        if (!$plan->stripe_price_id) {
            return back()->withErrors(['plan' => 'Payment not available for this plan. Please contact support.']);
        }

        try {
            $tenantDomain = tenant()->domains()->first()?->domain;
            $baseUrl      = 'https://' . $tenantDomain;

            $session = $this->stripeService->createCheckoutSession(
                tenantId: $tenantId,
                stripePriceId: $plan->stripe_price_id,
                customerEmail: $email,
                customerName: $name,
                successUrl: $baseUrl . '/billing/success',
                cancelUrl: $baseUrl . '/billing/expired',
                metadata: [
                    'plan_id'   => $plan->id,
                    'plan_name' => $plan->name,
                ]
            );

            return redirect()->away($session->url);

        } catch (\Exception $e) {
            Log::error('Stripe checkout failed for tenant ' . $tenantId . ': ' . $e->getMessage());
            return back()->withErrors(['checkout' => 'Payment initialization failed. Please try again.']);
        }
    }

    /**
     * Handle successful payment return from Stripe.
     * Route: GET /billing/success
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if ($sessionId) {
            try {
                $session = $this->stripeService->retrieveCheckoutSession($sessionId);

                if ($session->payment_status !== 'paid') {
                    Log::warning('Stripe success callback with non-paid session', [
                        'session_id'     => $sessionId,
                        'payment_status' => $session->payment_status,
                        'tenant'         => tenant('id'),
                    ]);

                    return redirect()->route('billing.expired')
                        ->withErrors(['payment' => 'Payment has not been confirmed yet. Please try again or contact support.']);
                }
            } catch (\Exception $e) {
                Log::error('Stripe session verification failed: ' . $e->getMessage(), [
                    'session_id' => $sessionId,
                    'tenant'     => tenant('id'),
                ]);
                // Non-fatal – let the webhook handle subscription activation
            }
        }

        $message = 'Your subscription has been activated successfully! Welcome aboard.';
        return redirect()->route('admin.dashboard')->with('success', $message);
    }

    /**
     * Redirect to Stripe Billing Portal for self-service.
     * Route: POST /billing/portal
     */
    public function portal(Request $request)
    {
        $tenantId = tenant('id');

        $subscription = DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('stripe_customer_id')
            ->orderByDesc('created_at')
            ->first();

        if (!$subscription?->stripe_customer_id) {
            return back()->withErrors(['portal' => 'No billing account found.']);
        }

        try {
            $tenantDomain = tenant()->domains()->first()?->domain;
            $session      = $this->stripeService->createBillingPortalSession(
                $subscription->stripe_customer_id,
                'https://' . $tenantDomain . '/admin/subscription'
            );

            return redirect()->away($session->url);

        } catch (\Exception $e) {
            Log::error('Billing portal failed: ' . $e->getMessage());
            return back()->withErrors(['portal' => 'Could not open billing portal. Please contact support.']);
        }
    }
}
