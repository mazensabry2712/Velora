<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetCentralLocale
{
    protected array $supported = ['en', 'ar', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja'];

    public function handle(Request $request, Closure $next)
    {
        $locale = session('central_locale', config('app.locale', 'en'));

        if (!in_array($locale, $this->supported)) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
