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
 *   - The user is not an Admin (Staff/Assistant skip onboarding)
 *   - Onboarding is already marked completed in settings
 */
class RedirectIfOnboardingIncomplete
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only affect authenticated, non-AJAX requests
        if (! auth()->check() || $request->expectsJson()) {
            return $next($request);
        }

        // Only affect admin-role users
        $user = auth()->user();
        if (! $user || strtolower($user->role ?? '') !== 'admin tenant') {
            return $next($request);
        }

        // Avoid redirect loop — pass through if already on onboarding or logout
        $currentRoute = $request->route()?->getName() ?? '';
        $passThrough  = [
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

        // Check settings in the current tenant DB
        $settings = Setting::first();

        if ($settings && ! $settings->onboarding_completed) {
            return redirect()->route('admin.onboarding');
        }

        return $next($request);
    }
}
