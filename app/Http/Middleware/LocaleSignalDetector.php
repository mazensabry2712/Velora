<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use NielsNumbers\LaravelLocalizer\Contracts\DetectorInterface;

class LocaleSignalDetector implements DetectorInterface
{
    public function detect(Request $request): string|array|null
    {
        $supported = config('localizer.supported_locales', []);
        $configuredDefault = config('localizer.omitted_locale', 'ar');
        $default = $configuredDefault;

        try {
            $systemDefault = SystemSetting::get('public_default_locale', $configuredDefault);
            if (is_string($systemDefault) && in_array($systemDefault, $supported, true)) {
                $default = $systemDefault;
            }
        } catch (\Throwable) {
            // Keep the configured fallback when central settings are unavailable.
        }

        // Explicit locale URLs must always win over persisted/session signals.
        // Laravel Localizer's detector can run before route parameters are
        // populated, so inspect the first URL segment as a reliable fallback.
        $path = trim($request->path(), '/');
        $firstSegment = $path === '' ? null : explode('/', $path, 2)[0];

        if (is_string($firstSegment) && in_array($firstSegment, $supported, true)) {
            return $firstSegment;
        }

        $routeLocale = $request->route('locale');
        if (is_string($routeLocale) && in_array($routeLocale, $supported, true)) {
            return $routeLocale;
        }

        $sessionLocale = $request->session()->get('central_locale');
        if (is_string($sessionLocale) && in_array($sessionLocale, $supported, true)) {
            return $sessionLocale;
        }

        $cookieLocale = $request->cookie('velora_locale_override');
        if (is_string($cookieLocale) && in_array($cookieLocale, $supported, true)) {
            return $cookieLocale;
        }

        return is_string($default) && in_array($default, $supported, true)
            ? $default
            : null;
    }
}
