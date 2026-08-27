<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Subscription\Contracts\SubscriptionAccessReader;
use App\Mail\FounderAlertMail;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnsureSubscriptionIsValid
{
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

    public function __construct(
        private readonly SubscriptionAccessReader $subscriptions,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (!tenant('id')) {
            return $next($request);
        }

        foreach ($this->excludedRoutes as $route) {
            if ($request->is('*/' . $route) || $request->is($route)) {
                return $next($request);
            }
        }

        $state = $this->subscriptions->currentState();
        $subscription = $state ? (object) $state : null;

        if (!$subscription) {
            return $this->redirectToExpired($request, 'no_subscription');
        }

        $now = now();

        if ($subscription->status === 'active') {
            if ($subscription->ends_at && $now->gt($subscription->ends_at)) {
                $this->transitionToGrace($subscription->id, $subscription->tenant_id);
                return $this->redirectToExpired($request, 'grace');
            }

            $this->shareBannerData($subscription);
            return $next($request);
        }

        if ($subscription->status === 'trial') {
            if ($subscription->trial_ends_at && $now->gt($subscription->trial_ends_at)) {
                $this->transitionToGrace($subscription->id, $subscription->tenant_id);
                $this->shareBannerData((object) array_merge((array) $subscription, ['status' => 'grace']));
                return $this->redirectToExpired($request, 'grace');
            }

            $trialDaysLeft = (int) $now->diffInDays($subscription->trial_ends_at, false);
            $trialDayElapsed = (int) now()->parse($subscription->created_at)->diffInDays($now);

            if ($trialDayElapsed >= 11 && ! $subscription->founder_alerted) {
                $this->triggerFounderAlert($subscription, 'Day 11 — no upgrade yet');
            }

            view()->share('subscriptionBanner', [
                'type' => $trialDaysLeft <= 3 ? 'warning' : 'info',
                'status' => 'trial',
                'days_left' => $trialDaysLeft,
                'trial_extended' => (bool) ($subscription->trial_extended ?? false),
                'upgrade_url' => '/admin/subscription/upgrade',
                'extend_url' => '/billing/extend-trial',
                'message' => $trialDaysLeft <= 2
                    ? "⏰ تنتهي تجربتك خلال {$trialDaysLeft} يوم! فعّل اشتراكك لتجنّب فقدان بياناتك."
                    : "🚀 فترة تجريبية: متبقى {$trialDaysLeft} يوم.",
            ]);

            return $next($request);
        }

        if ($subscription->status === 'grace') {
            if ($subscription->grace_ends_at && $now->gt($subscription->grace_ends_at)) {
                $this->transitionToExpired($subscription->id);
                return $this->redirectToExpired($request, 'expired');
            }

            $graceDaysLeft = (int) $now->diffInDays($subscription->grace_ends_at, false);

            view()->share('subscriptionBanner', [
                'type' => 'danger',
                'status' => 'grace',
                'days_left' => $graceDaysLeft,
                'message' => "🚨 Grace period: {$graceDaysLeft} day(s) left. Upgrade to restore full access.",
            ]);

            if ($this->isWriteOperation($request) && $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'SUBSCRIPTION_GRACE',
                    'message' => 'Your subscription has expired. Please upgrade to continue.',
                    'upgrade_url' => '/billing/expired',
                ], 403);
            }

            return $next($request);
        }

        if ($subscription->status === 'expired') {
            return $this->redirectToExpired($request, 'expired');
        }

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
                    'status' => 'grace',
                    'grace_ends_at' => now()->addDays(3),
                    'updated_at' => now(),
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
                    'status' => 'expired',
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
                'success' => false,
                'error' => 'SUBSCRIPTION_' . strtoupper($reason),
                'message' => 'Your subscription requires attention.',
                'upgrade_url' => '/billing/expired',
            ], 403);
        }

        return redirect('/billing/expired');
    }

    private function isWriteOperation(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && !$request->is('*/api/auth/*')
            && !$request->is('logout');
    }

    private function shareBannerData(object $subscription): void
    {
        if ($subscription->status === 'active' && $subscription->ends_at) {
            $daysLeft = (int) now()->diffInDays($subscription->ends_at, false);
            if ($daysLeft <= 7) {
                view()->share('subscriptionBanner', [
                    'type' => 'warning',
                    'status' => 'active',
                    'days_left' => $daysLeft,
                    'upgrade_url' => '/admin/subscription',
                    'message' => "تتجدد صلاحية اشتراكك خلال {$daysLeft} يوم.",
                ]);
            }
        }
    }

    private function triggerFounderAlert(object $subscription, string $reason): void
    {
        try {
            DB::connection('mysql')
                ->table('tenant_subscriptions')
                ->where('id', $subscription->id)
                ->update(['founder_alerted' => true, 'updated_at' => now()]);

            $founderEmail = config('mail.founder_email', config('mail.from.address'));
            if (! $founderEmail) {
                return;
            }

            $tenant = DB::connection('mysql')
                ->table('tenants')
                ->where('id', $subscription->tenant_id)
                ->first();

            $trialDaysLeft = $subscription->trial_ends_at
                ? max(0, (int) now()->diffInDays($subscription->trial_ends_at, false))
                : 0;

            Mail::to($founderEmail)->queue(new FounderAlertMail(
                tenantId: $subscription->tenant_id,
                businessName: $tenant?->name ?? $subscription->tenant_id,
                ownerEmail: $tenant?->email ?? 'unknown',
                triggerReason: $reason,
                trialDaysLeft: $trialDaysLeft,
            ));
        } catch (\Exception $e) {
            Log::error("Failed to send founder alert for tenant {$subscription->tenant_id}: " . $e->getMessage());
        }
    }
}
