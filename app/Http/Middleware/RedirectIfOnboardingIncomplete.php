<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RedirectIfOnboardingIncomplete
 *
 * After a tenant admin logs in, redirect them to the onboarding wizard
 * if they have not yet completed it.
 *
 * Skips the redirect if:
 *   - The request is already on the onboarding route (prevents redirect loop)
 *   - The request is an AJAX/API call
 *   - The user is not an Admin Tenant (Staff/Assistant skip onboarding)
 *   - Onboarding is already marked completed in settings
 */
class RedirectIfOnboardingIncomplete
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || $request->expectsJson()) {
            return $next($request);
        }

        $user = auth()->user();

        // Spatie Permission is the source of truth for tenant roles.
        if (! $user || ! $user->hasRole('Admin Tenant')) {
            return $next($request);
        }

        $currentRoute = $request->route()?->getName() ?? '';
        $passThrough = [
            'admin.onboarding',
            'admin.onboarding.step1',
            'admin.onboarding.step2',
            'admin.onboarding.step3',
            'admin.onboarding.complete',
            'logout',
            'billing.expired',
            'billing.checkout',
            'billing.portal',
        ];

        if (in_array($currentRoute, $passThrough, true)) {
            return $next($request);
        }

        $settings = Setting::first();

        if ($settings && ! $settings->onboarding_completed) {
            return redirect()->route('admin.onboarding');
        }

        return $next($request);
    }
}
