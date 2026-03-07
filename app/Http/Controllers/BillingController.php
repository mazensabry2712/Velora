<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\GeoService;
use App\Services\MoyasarService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(
        protected StripeService  $stripeService,
        protected MoyasarService $moyasarService,
        protected GeoService     $geoService,
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

        // ── Geo Pricing ──────────────────────────────────────────────────────
        $countryCode  = session('detected_country', 'US');
        $geoPrice     = $this->geoService->getPlanPrice($plan, $countryCode);
        $stripePriceId = $geoPrice?->stripe_price_id ?? $plan->stripe_price_id ?? null;
        $currency      = $geoPrice?->currency ?? session('current_currency', 'USD');
        $baseAmount    = (float) ($geoPrice?->amount ?? $plan->price ?? 0);
        $taxAmount     = $this->geoService->calculateTax($baseAmount, $countryCode);
        $totalAmount   = round($baseAmount + $taxAmount, 2);

        // ── Route Saudi users to Moyasar ────────────────────────────────────
        if ($countryCode === 'SA') {
            $amountSar     = (float) ($geoPrice?->amount ?? $plan->price ?? 0);
            $amountHalalas = (int) round($amountSar * 100);

            session([
                'moyasar_plan_id'   => $plan->id,
                'moyasar_amount'    => $amountHalalas,
                'moyasar_plan_name' => $plan->name,
                'moyasar_currency'  => 'SAR',
            ]);

            return redirect()->route('billing.moyasar.pay');
        }

        if (!$stripePriceId) {
            return back()->withErrors(['plan' => 'Payment not available for this plan. Please contact support.']);
        }

        try {
            $tenantDomain = tenant()->domains()->first()?->domain;
            $baseUrl      = 'https://' . $tenantDomain;

            $session = $this->stripeService->createCheckoutSession(
                tenantId: $tenantId,
                stripePriceId: $stripePriceId,
                customerEmail: $email,
                customerName: $name,
                successUrl: $baseUrl . '/billing/success',
                cancelUrl: $baseUrl . '/billing/expired',
                metadata: [
                    'plan_id'      => $plan->id,
                    'plan_name'    => $plan->name,
                    'country_code' => $countryCode,
                    'currency'     => $currency,
                    'base_amount'  => $baseAmount,
                    'tax_amount'   => $taxAmount,
                    'total_amount' => $totalAmount,
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

    // ── Moyasar (Saudi Payment Gateway) ───────────────────────────────────────

    /**
     * Show Moyasar hosted payment form (for SA tenants).
     * Route: GET /billing/moyasar/pay
     */
    public function moyasarPay(Request $request)
    {
        $planId   = session('moyasar_plan_id');
        $amount   = session('moyasar_amount');  // halalas

        if (!$planId || !$amount) {
            return redirect()->route('billing.expired')
                ->withErrors(['payment' => 'جلسة الدفع انتهت، الرجاء المحاولة مرة أخرى.']);
        }

        $plan        = SubscriptionPlan::findOrFail($planId);
        $callbackUrl = url('/billing/moyasar/callback') . '?plan_id=' . $planId;

        return view('billing.moyasar-pay', [
            'plan'           => $plan,
            'amount'         => $amount,
            'callbackUrl'    => $callbackUrl,
            'publishableKey' => $this->moyasarService->getPublishableKey(),
            'tenantId'       => tenant('id'),
        ]);
    }

    /**
     * Handle Moyasar redirect callback after payment.
     * Route: GET /billing/moyasar/callback
     */
    public function moyasarCallback(Request $request)
    {
        $paymentId = $request->query('id');
        $status    = $request->query('status');
        $planId    = $request->query('plan_id') ?? session('moyasar_plan_id');

        if (!$paymentId) {
            return redirect()->route('billing.expired')
                ->withErrors(['payment' => 'لم يتم استكمال الدفع.']);
        }

        try {
            $payment = $this->moyasarService->verifyPayment($paymentId);

            if (($payment['status'] ?? '') !== 'paid') {
                Log::warning('Moyasar callback: payment not paid', [
                    'payment_id' => $paymentId,
                    'status'     => $payment['status'] ?? 'unknown',
                    'tenant'     => tenant('id'),
                ]);
                return redirect()->route('billing.expired')
                    ->withErrors(['payment' => 'لم يتم تأكيد الدفع. تواصل مع الدعم إذا تم خصم المبلغ.']);
            }

            $tenantId       = tenant('id');
            $resolvedPlanId = $planId ?? ($payment['metadata']['plan_id'] ?? null);

            if (!$resolvedPlanId) {
                Log::error('Moyasar callback: missing plan_id', ['payment_id' => $paymentId]);
                return redirect()->route('billing.expired')
                    ->withErrors(['payment' => 'حدث خطأ في تحديد الخطة. الرجاء التواصل مع الدعم.']);
            }

            $this->moyasarService->activateSubscription(
                $tenantId,
                (int) $resolvedPlanId,
                (int) ($payment['amount'] ?? 0),
                $paymentId
            );

            session()->forget(['moyasar_plan_id', 'moyasar_amount', 'moyasar_plan_name', 'moyasar_currency']);

            return redirect()->route('admin.dashboard')
                ->with('success', 'تم تفعيل اشتراكك بنجاح! 🎉');

        } catch (\Exception $e) {
            Log::error('Moyasar callback error: ' . $e->getMessage(), [
                'payment_id' => $paymentId,
                'tenant'     => tenant('id'),
            ]);
            return redirect()->route('billing.expired')
                ->withErrors(['payment' => 'حدث خطأ أثناء تفعيل الاشتراك. الرجاء التواصل مع الدعم.']);
        }
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

    // ── Trial Extension ─────────────────────────────────────────────────

    /**
     * Grant a one-time 7-day trial extension.
     * Route: POST /billing/extend-trial
     * Conditions: status=trial AND trial_extended=false
     */
    public function extendTrial(Request $request)
    {
        $tenantId = tenant('id');
        $centralConnection = config('tenancy.database.central_connection', 'mysql');

        $subscription = DB::connection($centralConnection)
            ->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'trial')
            ->orderByDesc('created_at')
            ->first();

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active trial found.',
            ], 422);
        }

        if ($subscription->trial_extended) {
            return response()->json([
                'success' => false,
                'message' => 'Trial has already been extended once.',
            ], 422);
        }

        $newTrialEndsAt = now()->parse($subscription->trial_ends_at)->addDays(7);

        DB::connection($centralConnection)
            ->table('tenant_subscriptions')
            ->where('id', $subscription->id)
            ->update([
                'trial_ends_at'      => $newTrialEndsAt,
                'trial_extended'     => true,
                'trial_extended_at'  => now(),
                'updated_at'         => now(),
            ]);

        Log::info("Trial extended 7 days for tenant {$tenantId}. New trial_ends_at: {$newTrialEndsAt}");

        if ($request->expectsJson()) {
            return response()->json([
                'success'          => true,
                'message'          => 'Trial extended by 7 days.',
                'new_trial_ends_at' => $newTrialEndsAt->toDateTimeString(),
            ]);
        }

        return back()->with('success', 'تم تمديد فترة تجربتك 7 أيام إضافية!');
    }
}
