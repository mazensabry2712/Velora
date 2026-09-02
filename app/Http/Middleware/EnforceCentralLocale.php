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
            // Keep the configured omitted locale if central settings are unavailable.
        }

        // Super Admin has its own persisted interface preference. Keep that
        // preference isolated from the public site's URL/default locale policy.
        if ($request->is('super-admin') || $request->is('super-admin/*')) {
            $locale = session('central_locale', $default);

            if (! is_string($locale) || ! in_array($locale, $supported, true)) {
                $locale = $default;
            }

            App::setLocale($locale);
            session()->put('central_locale', $locale);

            return $next($request);
        }

        $path = trim($request->path(), '/');
        $firstSegment = $path === '' ? null : explode('/', $path, 2)[0];

        // An explicit supported locale in the public URL is authoritative.
        if (is_string($firstSegment) && in_array($firstSegment, $supported, true)) {
            App::setLocale($firstSegment);
            session()->put('central_locale', $firstSegment);

            return $next($request);
        }

        // Unprefixed public routes always use the configured public default.
        App::setLocale($default);
        session()->put('central_locale', $default);

        return $next($request);
    }
}
