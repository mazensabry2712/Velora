<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetCentralLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supported = config('locales.supported', ['ar', 'en', 'fr']);
        $default = config('locales.default', 'ar');

        $locale = session('central_locale', $default);

        if (!in_array($locale, $supported, true)) {
            $locale = $default;
        }

        // Persist the resolved locale so views that read the session directly
        // use the same default and never fall back to a different language.
        session()->put('central_locale', $locale);
        App::setLocale($locale);

        return $next($request);
    }
}
