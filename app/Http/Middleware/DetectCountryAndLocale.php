<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use App\Services\GeoService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Detects the visitor's country and resolves the central locale/currency.
 * An explicit locale in a Laravel Localizer route always wins.
 */
class DetectCountryAndLocale
{
    public function __construct(private GeoService $geo) {}

    public function handle(Request $request, Closure $next): Response
    {
        $country = $this->resolveCountry($request);
        Session::put('detected_country', $country);

        if (! $this->hasExplicitRouteLocale($request)) {
            App::setLocale($this->resolveLocale($request));
        }

        Session::put('central_locale', App::getLocale());
        Session::put('current_currency', $this->resolveCurrency($request, $country));

        return $next($request);
    }

    private function hasExplicitRouteLocale(Request $request): bool
    {
        $routeLocale = $request->route('locale');
        $supported = config('localizer.supported_locales', config('locales.supported', ['ar', 'en', 'fr']));

        return is_string($routeLocale) && in_array($routeLocale, $supported, true);
    }

    private function resolveCountry(Request $request): string
    {
        if (app()->environment('local', 'staging') && $request->query('_country')) {
            return strtoupper($request->query('_country'));
        }

        return SystemSetting::get('geo_detection_enabled', true)
            ? $this->geo->getCountryCode($request)
            : 'US';
    }

    private function resolveLocale(Request $request): string
    {
        $supported = config('localizer.supported_locales', config('locales.supported', ['ar', 'en', 'fr']));
        $default = config('locales.default', config('localizer.supported_locales.0', 'ar'));

        if (SystemSetting::get('allow_manual_language_switch', true)) {
            $cookieLocale = $request->cookie('velora_locale_override');
            if ($cookieLocale && in_array($cookieLocale, $supported, true)) {
                return $cookieLocale;
            }
        }

        $sessionLocale = Session::get('central_locale');
        if ($sessionLocale && in_array($sessionLocale, $supported, true)) {
            return $sessionLocale;
        }

        return $default;
    }

    private function resolveCurrency(Request $request, string $country): string
    {
        if (SystemSetting::get('allow_manual_currency_switch', true)) {
            $cookieCurrency = $request->cookie('velora_currency_override');
            if ($cookieCurrency && strlen($cookieCurrency) === 3) {
                return strtoupper($cookieCurrency);
            }
        }

        if (Session::has('current_currency')) {
            return Session::get('current_currency');
        }

        return $this->geo->getCurrencyForCountry($country);
    }
}
