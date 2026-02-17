<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetTenantLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if language is passed in URL
        if ($request->has('lang') && in_array($request->query('lang'), ['en', 'ar'])) {
            $locale = $request->query('lang');
            session()->put('locale', $locale);
            App::setLocale($locale);
        }
        // Check if user has selected a language in session
        elseif (session()->has('locale')) {
            $locale = session('locale');
            App::setLocale($locale);
        } else {
            // Fall back to tenant settings or default
            try {
                $tenant = tenant();

                if ($tenant && isset($tenant->settings) && isset($tenant->settings->language)) {
                    App::setLocale($tenant->settings->language);
                } else {
                    App::setLocale(config('app.locale', 'ar'));
                }
            } catch (\Exception $e) {
                App::setLocale(config('app.locale', 'ar'));
            }
        }

        return $next($request);
    }
}
