<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Subscription\Contracts\SubscriptionAccessReader;
use App\Domain\Subscription\SubscriptionLifecycle;
use App\Mail\FounderAlertMail;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class EnsureSubscriptionIsValid
{
    /**
     * Routes that must remain reachable when the tenant is read-only/locked.
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
        'api/auth/login',
        'api/auth/logout',
    ];

    public function __construct(
        private readonly SubscriptionAccessReader $subscriptions,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! tenant('id') || $this->isExcluded($request)) {
            return $next($request);
        }

        $state = $this->subscriptions->currentState();
        $subscription = $state ? (object) $state : null;

        if (! $subscription) {
            return $this->redirectToBilling($request, 'no_subscription');
        }

        $now = now();

        // Active paid subscriptions have full access until their paid period ends.
        if ($subscription->status === 'active') {
            if (! empty($subscription->ends_at) && $now->gte($subscription->ends_at)) {
                $this->moveToReadOnly($subscription);
                $subscription = $this->freshSubscriptionState($subscription->id, $subscription);
            } else {
                $this->shareActiveBanner($subscription);
                return $next($request);
            }
        }

        // Trial gets full access for exactly seven days. No grace period.
        if ($subscription->status === 'trial') {
            if (empty($subscription->trial_ends_at) || $now->lt($subscription->trial_ends_at)) {
                $this->shareTrialBanner($subscription);
                return $next($request);
            }

            $this->moveToReadOnly($subscription);
            $subscription = $this->freshSubscriptionState($subscription->id, $subscription);
        }

        // Legacy grace rows are migrated into the canonical read-only phase.
        if ($subscription->status === 'grace' || $subscription->status === 'expired') {
            $this->moveLegacyExpiredStateToLifecycle($subscription);
            $subscription = $this->freshSubscriptionState($subscription->id, $subscription);
        }

        if ($subscription->status === 'read_only') {
            $readOnlyEndsAt = $subscription->read_only_ends_at
                ? now()->parse($subscription->read_only_ends_at)
                : now()->addSeconds(1);

            if ($now->lt($readOnlyEndsAt)) {
                $daysLeft = max(1, (int) $now->diffInDays($readOnlyEndsAt, false));

                view()->share('subscriptionBanner', [
                    'type' => 'warning',
                    'status' => 'read_only',
                    'days_left' => $daysLeft,
                    'upgrade_url' => '/admin/subscription/upgrade',
                    'message' => "🔒 انتهت الفترة التجريبية. حسابك للقراءة فقط لمدة {$daysLeft} يوم. ادفع لتستعيد التعديل الكامل.",
                ]);

                if ($this->isWriteOperation($request)) {
                    return $this->subscriptionWriteBlockedResponse($request, 'SUBSCRIPTION_READ_ONLY');
                }

                return $next($request);
            }

            $this->moveToLocked($subscription);
            $subscription = $this->freshSubscriptionState($subscription->id, $subscription);
        }

        if ($subscription->status === 'locked') {
            $deletionAt = $subscription->deletion_at
                ? now()->parse($subscription->deletion_at)
                : now();

            if ($now->gte($deletionAt)) {
                // Do not delete from a request. The scheduled purge command owns permanent deletion.
                return $this->redirectToBilling($request, 'pending_deletion');
            }

            view()->share('subscriptionLock', [
                'status' => 'locked',
                'deletion_at' => $deletionAt,
                'days_until_deletion' => max(1, (int) $now->diffInDays($deletionAt, false)),
                'upgrade_url' => '/admin/subscription/upgrade',
            ]);

            return $this->redirectToBilling($request, 'locked');
        }

        if ($subscription->status === 'cancelled' || $subscription->status === 'suspended') {
            return $this->redirectToBilling($request, $subscription->status);
        }

        return $next($request);
    }

    private function isExcluded(Request $request): bool
    {
        foreach ($this->excludedRoutes as $route) {
            if ($request->is($route) || $request->is('*/' . $route)) {
                return true;
            }
        }

        return false;
    }

    private function moveToReadOnly(object $subscription): void
    {
        $anchor = $subscription->status === 'trial'
            ? now()->parse($subscription->trial_ends_at)
            : now()->parse($subscription->ends_at);

        $readOnlyEndsAt = SubscriptionLifecycle::readOnlyEndsAt($anchor);
        $deletionAt = SubscriptionLifecycle::deletionAt($anchor);

        DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->where('id', $subscription->id)
            ->update([
                'status' => 'read_only',
                'read_only_ends_at' => $readOnlyEndsAt,
                'locked_at' => $readOnlyEndsAt,
                'deletion_at' => $deletionAt,
                'updated_at' => now(),
            ]);
    }

    private function moveToLocked(object $subscription): void
    {
        DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->where('id', $subscription->id)
            ->update([
                'status' => 'locked',
                'locked_at' => $subscription->locked_at ?? now(),
                'updated_at' => now(),
            ]);
    }

    private function moveLegacyExpiredStateToLifecycle(object $subscription): void
    {
        $anchor = null;

        if (! empty($subscription->trial_ends_at)) {
            $anchor = now()->parse($subscription->trial_ends_at);
        } elseif (! empty($subscription->ends_at)) {
            $anchor = now()->parse($subscription->ends_at);
        } elseif (! empty($subscription->grace_ends_at)) {
            $anchor = now()->parse($subscription->grace_ends_at)->subDays(3);
        }

        if (! $anchor) {
            return;
        }

        $readOnlyEndsAt = SubscriptionLifecycle::readOnlyEndsAt($anchor);
        $deletionAt = SubscriptionLifecycle::deletionAt($anchor);
        $status = now()->lt($readOnlyEndsAt) ? 'read_only' : (now()->lt($deletionAt) ? 'locked' : 'locked');

        DB::connection('mysql')
            ->table('tenant_subscriptions')
            ->where('id', $subscription->id)
            ->update([
                'status' => $status,
                'read_only_ends_at' => $readOnlyEndsAt,
                'locked_at' => $readOnlyEndsAt,
                'deletion_at' => $deletionAt,
                'updated_at' => now(),
            ]);
    }

    private function freshSubscriptionState(int $id, object $fallback): object
    {
        $fresh = DB::connection('mysql')->table('tenant_subscriptions')->where('id', $id)->first();

        return $fresh ? (object) $fresh : $fallback;
    }

    private function subscriptionWriteBlockedResponse(Request $request, string $code): mixed
    {
        if ($request->expectsJson() || $request->is('*/api/*')) {
            return response()->json([
                'success' => false,
                'error' => $code,
                'message' => 'Your trial has ended. Upgrade to restore full access.',
                'upgrade_url' => '/admin/subscription/upgrade',
            ], 403);
        }

        return redirect('/billing/expired')->withErrors([
            'subscription' => 'Your trial has ended. Please upgrade to continue making changes.',
        ]);
    }

    private function redirectToBilling(Request $request, string $reason): mixed
    {
        if ($request->expectsJson() || $request->is('*/api/*')) {
            return response()->json([
                'success' => false,
                'error' => 'SUBSCRIPTION_' . strtoupper($reason),
                'message' => $reason === 'locked'
                    ? 'Your account is locked. Please upgrade to restore access.'
                    : 'Your subscription requires attention.',
                'upgrade_url' => '/admin/subscription/upgrade',
            ], 403);
        }

        return redirect('/billing/expired');
    }

    private function shareTrialBanner(object $subscription): void
    {
        $endsAt = $subscription->trial_ends_at ? now()->parse($subscription->trial_ends_at) : now();
        $daysLeft = max(0, (int) now()->diffInDays($endsAt, false));

        view()->share('subscriptionBanner', [
            'type' => $daysLeft <= 3 ? 'warning' : 'info',
            'status' => 'trial',
            'days_left' => $daysLeft,
            'trial_extended' => (bool) ($subscription->trial_extended ?? false),
            'upgrade_url' => '/admin/subscription/upgrade',
            'extend_url' => '/billing/extend-trial',
            'message' => $daysLeft <= 2
                ? "⏰ تنتهي تجربتك خلال {$daysLeft} يوم! فعّل اشتراكك قبل انتهاء التجربة."
                : "🚀 فترة تجريبية: متبقى {$daysLeft} يوم.",
        ]);

        $trialDayElapsed = $subscription->created_at
            ? (int) now()->parse($subscription->created_at)->diffInDays(now())
            : 0;

        if ($trialDayElapsed >= 6 && ! ($subscription->founder_alerted ?? false)) {
            $this->triggerFounderAlert($subscription, 'Day 6 — trial ending soon');
        }
    }

    private function shareActiveBanner(object $subscription): void
    {
        if (! empty($subscription->ends_at)) {
            $daysLeft = (int) now()->diffInDays(now()->parse($subscription->ends_at), false);
            if ($daysLeft <= 7) {
                view()->share('subscriptionBanner', [
                    'type' => 'warning',
                    'status' => 'active',
                    'days_left' => $daysLeft,
                    'upgrade_url' => '/admin/subscription',
                    'message' => "تنتهي صلاحية اشتراكك خلال {$daysLeft} يوم.",
                ]);
            }
        }
    }

    private function isWriteOperation(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && ! $request->is('*/api/auth/*')
            && ! $request->is('logout');
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
                ? max(0, (int) now()->diffInDays(now()->parse($subscription->trial_ends_at), false))
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
