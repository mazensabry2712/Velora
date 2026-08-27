<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use NielsNumbers\LaravelLocalizer\Contracts\DetectorInterface;

class LocaleSignalDetector implements DetectorInterface
{
    /**
     * Resolve locale signals with explicit URL locale taking precedence.
     *
     * Resolution order:
     * 1. Explicit /{locale} route segment.
     * 2. Explicit Velora session override.
     * 3. Explicit Velora cookie override.
     * 4. The configured omitted/default locale.
     *
     * @return string|array<int, string>|null
     */
    public function detect(Request $request): string|array|null
    {
        $supported = config('localizer.supported_locales', []);
        $default = config('localizer.omitted_locale', 'ar');

        // An explicit locale in the URL must always win over persisted state.
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
