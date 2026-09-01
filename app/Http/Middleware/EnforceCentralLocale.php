<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class EnforceCentralLocale
{
    public function handle(Request $request, Closure $next)
    {
        $centralHost = (string) env('APP_DOMAIN', 'velora.test');

        if ($request->getHost() !== $centralHost) {
            return $next($request);
        }

        $supported = config('localizer.supported_locales', []);
        if (! is_array($supported) || $supported === []) {
            return $next($request);
        }

        $default = config('localizer.omitted_locale', 'ar');

        try {
            $configuredDefault = SystemSetting::get('public_default_locale', $default);
            if (is_string($configuredDefault) && in_array($configuredDefault, $supported, true)) {
                $default = $configuredDefault;
            }
        } catch (\Throwable) {
            // Fall back to the configured omitted locale when central settings
            // are temporarily unavailable.
        }

        $path = trim($request->path(), '/');
        $firstSegment = $path === '' ? null : explode('/', $path, 2)[0];

        // An explicit supported locale in the URL is always authoritative.
        if (is_string($firstSegment) && in_array($firstSegment, $supported, true)) {
            App::setLocale($firstSegment);
            session()->put('central_locale', $firstSegment);

            return $next($request);
        }

        // Unprefixed central routes use the current public default. This is
        // intentionally calculated per request so a Super Admin change is
        // reflected without relying on a previous request's app/session state.
        App::setLocale($default);
        session()->put('central_locale', $default);

        return $next($request);
    }
}
