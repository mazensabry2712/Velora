<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnsureSubscriptionIsValid
{
    /**
     * These routes are always accessible regardless of subscription status.
     */
    protected array $excludedRoutes = [
        'billing/expired',
        'billing/success',
        'billing/checkout',
        'billing/portal',
        'billing/history',
        'login',
        'logout',
        'change-language',
        'admin/subscription',
        'admin/subscription/billing',
        'admin/subscription/upgrade',
        'admin/subscription/request-upgrade',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $tenantId = tenant('id');
        if (!$tenantId) {
            return $next($request);
        }

        // Skip for excluded routes
        foreach ($this->excludedRoutes as $route) {
            if ($request->is('*/' . $route) || $request->is($route)) {
                return $next($request);
            }
        }

        $subscription = DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->first();

        // No subscription at all → expired
        if (!$subscription) {
            return $this->redirectToExpired($request, 'no_subscription');
        }

        $now = now();

        // ── Active subscription ──────────────────────────────────────────
        if ($subscription->status === 'active') {
            if ($subscription->ends_at && $now->gt($subscription->ends_at)) {
                // Active subscription expired → set grace
                $this->transitionToGrace($subscription->id, $subscription->tenant_id);
                return $this->redirectToExpired($request, 'grace');
            }

            // Show days-remaining banner via shared data
            $this->shareBannerData($subscription);
            return $next($request);
        }

        // ── Trial ────────────────────────────────────────────────────────
        if ($subscription->status === 'trial') {
            if ($subscription->trial_ends_at && $now->gt($subscription->trial_ends_at)) {
                // Trial ended → move to grace
                $this->transitionToGrace($subscription->id, $subscription->tenant_id);
                $this->shareBannerData((object) array_merge((array) $subscription, ['status' => 'grace']));
                return $this->redirectToExpired($request, 'grace');
            }

            $trialDaysLeft = (int) $now->diffInDays($subscription->trial_ends_at, false);

            // Warning banner 3 days before trial ends
            view()->share('subscriptionBanner', [
                'type'      => $trialDaysLeft <= 3 ? 'warning' : 'info',
                'status'    => 'trial',
                'days_left' => $trialDaysLeft,
                'message'   => $trialDaysLeft <= 3
                    ? "⚠️ Your trial ends in {$trialDaysLeft} day(s)! Upgrade now to keep access."
                    : "🎉 Trial: {$trialDaysLeft} days remaining.",
            ]);

            // Only block destructive actions when < 0 days (grace mode)
            return $next($request);
        }

        // ── Grace Period ─────────────────────────────────────────────────
        if ($subscription->status === 'grace') {
            if ($subscription->grace_ends_at && $now->gt($subscription->grace_ends_at)) {
                // Grace ended → expired
                $this->transitionToExpired($subscription->id);
                return $this->redirectToExpired($request, 'expired');
            }

            $graceDaysLeft = (int) $now->diffInDays($subscription->grace_ends_at, false);

            view()->share('subscriptionBanner', [
                'type'      => 'danger',
                'status'    => 'grace',
                'days_left' => $graceDaysLeft,
                'message'   => "🚨 Grace period: {$graceDaysLeft} day(s) left. Upgrade to restore full access.",
            ]);

            // Block write operations during grace
            if ($this->isWriteOperation($request)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'error'   => 'SUBSCRIPTION_GRACE',
                        'message' => 'Your subscription has expired. Please upgrade to continue.',
                        'upgrade_url' => '/billing/expired',
                    ], 403);
                }
            }

            return $next($request);
        }

        // ── Expired ──────────────────────────────────────────────────────
        if ($subscription->status === 'expired') {
            return $this->redirectToExpired($request, 'expired');
        }

        // ── Cancelled ────────────────────────────────────────────────────
        if ($subscription->status === 'cancelled') {
            return $this->redirectToExpired($request, 'cancelled');
        }

        return $next($request);
    }

    private function transitionToGrace(int $subscriptionId, string $tenantId): void
    {
        try {
            DB::connection('mysql')
                ->table('tenant_subscriptions')
                ->where('id', $subscriptionId)
                ->update([
                    'status'        => 'grace',
                    'grace_ends_at' => now()->addDays(3),
                    'updated_at'    => now(),
                ]);
        } catch (\Exception $e) {
            Log::error("Failed to transition tenant {$tenantId} to grace: " . $e->getMessage());
        }
    }

    private function transitionToExpired(int $subscriptionId): void
    {
        try {
            DB::connection('mysql')
                ->table('tenant_subscriptions')
                ->where('id', $subscriptionId)
                ->update([
                    'status'     => 'expired',
                    'updated_at' => now(),
                ]);
        } catch (\Exception $e) {
            Log::error("Failed to transition subscription {$subscriptionId} to expired: " . $e->getMessage());
        }
    }

    private function redirectToExpired(Request $request, string $reason): mixed
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success'    => false,
                'error'      => 'SUBSCRIPTION_' . strtoupper($reason),
                'message'    => 'Your subscription requires attention.',
                'upgrade_url' => '/billing/expired',
            ], 403);
        }

        // Already on billing page - avoid redirect loop
        if ($request->is('billing/expired') || $request->is('*/billing/expired')) {
            return redirect('/billing/expired');
        }

        return redirect('/billing/expired');
    }

    private function isWriteOperation(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])
            && !$request->is('*/api/auth/*')
            && !$request->is('logout');
    }

    private function shareBannerData(object $subscription): void
    {
        if ($subscription->status === 'active' && $subscription->ends_at) {
            $daysLeft = (int) now()->diffInDays($subscription->ends_at, false);
            if ($daysLeft <= 7) {
                view()->share('subscriptionBanner', [
                    'type'      => 'warning',
                    'status'    => 'active',
                    'days_left' => $daysLeft,
                    'message'   => "Your subscription renews in {$daysLeft} day(s).",
                ]);
            }
        }
    }
}
