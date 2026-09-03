<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Billing\Actions\ExtendTrial;
use App\Application\Billing\Actions\GetExpiredBillingOverview;
use App\Domain\Shared\Contracts\PaymentGatewayResolver;
use App\Models\SubscriptionPlan;
use App\Payments\PaymentGatewayManager;
use App\Services\GeoService;
use App\Services\MoyasarService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

final class BillingController extends Controller
{
    public function __construct(
        protected PaymentGatewayManager $paymentManager,
        protected PaymentGatewayResolver $gatewayRouter,
        protected GeoService $geoService,
        protected StripeService $stripeService,
        protected MoyasarService $moyasarService,
        private readonly GetExpiredBillingOverview $getExpiredBillingOverview,
        private readonly ExtendTrial $extendTrial,
    ) {}

    public function expired()
    {
        $overview = $this->getExpiredBillingOverview->execute(tenant('id'));

        return view('billing.expired', $overview);
    }

    public function checkout(Request $request)
    {
        $centralConn = config('tenancy.database.central_connection', 'mysql');
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', Rule::exists("{$centralConn}.subscription_plans", 'id')],
        ]);

        $tenantId = tenant('id');
        $tenantData = DB::connection($centralConn)->table('tenants')->where('id', $tenantId)->first();
        $data = json_decode((string) ($tenantData?->data ?? '{}'), true) ?? [];
        $name = data_get($data, 'name', 'Tenant');
        $email = data_get($data, 'email', 'tenant@velora.com');
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        $countryCode = session('detected_country', 'US');
        $geoPrice = $this->geoService->getPlanPrice($plan, $countryCode);
        $stripePriceId = $geoPrice?->stripe_price_id ?? $plan->stripe_price_id ?? null;
        $currency = $geoPrice?->currency ?? session('current_currency', 'USD');
        $baseAmount = (float) ($geoPrice?->amount ?? $plan->price ?? 0);
        $taxAmount = $this->geoService->calculateTax($baseAmount, $countryCode);
        $totalAmount = round($baseAmount + $taxAmount, 2);

        $availableGateways = $this->gatewayRouter->forCountry($countryCode);
        $primaryGateway = $availableGateways[0] ?? 'stripe';
        $tenantDomain = tenant()->domains()->first()?->domain;
        $baseUrl = 'https://' . $tenantDomain;

        if ($primaryGateway === 'stripe' && !$stripePriceId) {
            return back()->withErrors(['plan' => 'Payment not available for this plan. Please contact support.']);
        }

        try {
            $checkoutData = [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'tenant_id' => $tenantId,
                'customer_email' => $email,
                'customer_name' => $name,
                'success_url' => $baseUrl . '/billing/success',
                'cancel_url' => $baseUrl . '/billing/expired',
                'amount' => $baseAmount,
                'currency' => $currency,
                'country_code' => $countryCode,
                'stripe_price_id' => $stripePriceId,
                'metadata' => [
                    'plan_name' => $plan->name,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                ],
            ];

            $redirectUrl = $this->paymentManager->driver($primaryGateway)->createCheckout($checkoutData);
            Log::info("BillingController: tenant {$tenantId} initiated checkout via [{$primaryGateway}] for plan {$plan->id}");

            return redirect()->away($redirectUrl);
        } catch (\Exception $e) {
            Log::error("BillingController: checkout failed via [{$primaryGateway}] for tenant {$tenantId}: " . $e->getMessage());
            return back()->withErrors(['checkout' => 'Payment initialization failed. Please try again.']);
        }
    }

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        if ($sessionId) {
            try {
                $session = $this->stripeService->retrieveCheckoutSession($sessionId);
                if ($session->payment_status !== 'paid') {
                    Log::warning('Stripe success callback with non-paid session', [
                        'session_id' => $sessionId,
                        'payment_status' => $session->payment_status,
                        'tenant' => tenant('id'),
                    ]);
                    return redirect()->route('billing.expired')
                        ->withErrors(['payment' => 'Payment has not been confirmed yet. Please try again or contact support.']);
                }
            } catch (\Exception $e) {
                Log::error('Stripe session verification failed: ' . $e->getMessage(), [
                    'session_id' => $sessionId,
                    'tenant' => tenant('id'),
                ]);
            }
        }

        return redirect()->route('admin.dashboard')
            ->with('success', 'Your subscription has been activated successfully! Welcome aboard.');
    }

    public function moyasarPay(Request $request)
    {
        $planId = session('moyasar_plan_id');
        $amount = session('moyasar_amount');
        if (!$planId || !$amount) {
            return redirect()->route('billing.expired')
                ->withErrors(['payment' => 'جلسة الدفع انتهت، الرجاء المحاولة مرة أخرى.']);
        }

        $plan = SubscriptionPlan::findOrFail($planId);
        $callbackUrl = url('/billing/moyasar/callback') . '?plan_id=' . $planId;
        $moyasarGateway = $this->paymentManager->driver('moyasar');

        return view('billing.moyasar-pay', [
            'plan' => $plan,
            'amount' => $amount,
            'callbackUrl' => $callbackUrl,
            'publishableKey' => $moyasarGateway->getPublishableKey(),
            'tenantId' => tenant('id'),
        ]);
    }

    public function moyasarCallback(Request $request)
    {
        $paymentId = $request->query('id');

        if (!$paymentId) {
            return redirect()->route('billing.expired')
                ->withErrors(['payment' => 'لم يتم استكمال الدفع.']);
        }

        try {
            $payment = $this->moyasarService->verifyPayment($paymentId);
            $expectedAmount = (int) session('moyasar_amount', 0);
            $expectedCurrency = strtoupper((string) session('moyasar_currency', 'SAR'));
            $paymentAmount = (int) ($payment['amount'] ?? 0);
            $paymentCurrency = strtoupper((string) ($payment['currency'] ?? ''));

            if (($payment['status'] ?? null) !== 'paid') {
                Log::warning('Moyasar callback: payment not paid', [
                    'payment_id' => $paymentId,
                    'status' => $payment['status'] ?? 'unknown',
                    'tenant' => tenant('id'),
                ]);

                return redirect()->route('billing.expired')
                    ->withErrors(['payment' => 'لم يتم تأكيد الدفع. تواصل مع الدعم إذا تم خصم المبلغ.']);
            }

            if (($expectedAmount > 0 && $paymentAmount !== $expectedAmount) || $paymentCurrency !== $expectedCurrency) {
                Log::error('Moyasar callback: verified payment does not match checkout session', [
                    'payment_id' => $paymentId,
                    'expected_amount' => $expectedAmount,
                    'payment_amount' => $paymentAmount,
                    'expected_currency' => $expectedCurrency,
                    'payment_currency' => $paymentCurrency,
                    'tenant' => tenant('id'),
                ]);

                return redirect()->route('billing.expired')
                    ->withErrors(['payment' => 'بيانات الدفع لا تطابق عملية الشراء الأصلية. تواصل مع الدعم.']);
            }

            // The browser callback is informational only. Subscription state is
            // changed exclusively by the authenticated/idempotent webhook.
            session()->forget(['moyasar_plan_id', 'moyasar_amount', 'moyasar_plan_name', 'moyasar_currency']);

            return redirect()->route('admin.dashboard')
                ->with('success', 'تم استلام الدفع بنجاح، وسيتم تفعيل الاشتراك تلقائيًا بعد تأكيد مزود الدفع.');
        } catch (\Exception $e) {
            Log::error('Moyasar callback error: ' . $e->getMessage(), [
                'payment_id' => $paymentId,
                'tenant' => tenant('id'),
            ]);
            return redirect()->route('billing.expired')
                ->withErrors(['payment' => 'حدث خطأ أثناء التحقق من الدفع. الرجاء التواصل مع الدعم.']);
        }
    }

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
            $session = $this->stripeService->createBillingPortalSession(
                $subscription->stripe_customer_id,
                'https://' . $tenantDomain . '/admin/subscription'
            );
            return redirect()->away($session->url);
        } catch (\Exception $e) {
            Log::error('Billing portal failed: ' . $e->getMessage());
            return back()->withErrors(['portal' => 'Could not open billing portal. Please contact support.']);
        }
    }

    public function extendTrial(Request $request)
    {
        $result = $this->extendTrial->execute(tenant('id'));

        return match ($result['status']) {
            'missing' => response()->json(['success' => false, 'message' => 'No active trial found.'], 422),
            'already_extended' => response()->json(['success' => false, 'message' => 'Trial has already been extended once.'], 422),
            'extended' => $this->extendedTrialResponse($request, $result['new_trial_ends_at']),
            default => response()->json(['success' => false, 'message' => 'Unable to extend trial.'], 422),
        };
    }

    private function extendedTrialResponse(Request $request, \DateTimeInterface $newTrialEndsAt)
    {
        Log::info("Trial extended 7 days for tenant " . tenant('id') . ". New trial_ends_at: {$newTrialEndsAt->format('Y-m-d H:i:s')}");

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Trial extended by 7 days.',
                'new_trial_ends_at' => $newTrialEndsAt->format('Y-m-d H:i:s'),
            ]);
        }

        return back()->with('success', 'تم تمديد فترة تجربتك 7 أيام إضافية!');
    }
}
