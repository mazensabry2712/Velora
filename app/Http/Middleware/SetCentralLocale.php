<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetCentralLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supported = config('locales.supported', config('localizer.supported_locales', ['ar', 'en', 'fr']));
        $default = config('locales.default', config('localizer.supported_locales.0', 'ar'));

        // Laravel Localizer is authoritative when the current route contains
        // an explicit {locale}. Never overwrite a locale chosen from the URL.
        $routeLocale = $request->route('locale');
        if (is_string($routeLocale) && in_array($routeLocale, $supported, true)) {
            App::setLocale($routeLocale);
            session()->put('central_locale', $routeLocale);

            return $next($request);
        }

        $locale = session('central_locale', $default);

        if (! in_array($locale, $supported, true)) {
            $locale = $default;
        }

        session()->put('central_locale', $locale);
        App::setLocale($locale);

        return $next($request);
    }
}
