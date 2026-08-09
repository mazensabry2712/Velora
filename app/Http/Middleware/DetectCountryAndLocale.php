<?php

namespace App\Http\Middleware;

use App\Services\GeoService;
use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Detects the visitor's country via Cloudflare CF-IPCountry header,
 * then sets the app locale and preferred currency in the session.
 *
 * Manual overrides are stored in cookies and always take priority.
 *
 * Session keys written:
 *   - detected_country   (e.g. "DE")
 *   - central_locale     (e.g. "de")
 *   - current_currency   (e.g. "EUR")
 */
class DetectCountryAndLocale
{
    public function __construct(private GeoService $geo) {}

    public function handle(Request $request, Closure $next): Response
    {
        // ── 1. Determine country code ──────────────────────────────────────
        $country = $this->resolveCountry($request);
        Session::put('detected_country', $country);

        // ── 2. Locale ──────────────────────────────────────────────────────
        $locale = $this->resolveLocale($request, $country);
        App::setLocale($locale);
        Session::put('central_locale', $locale);

        // ── 3. Currency ────────────────────────────────────────────────────
        $currency = $this->resolveCurrency($request, $country);
        Session::put('current_currency', $currency);

        return $next($request);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function resolveCountry(Request $request): string
    {
        // Manual URL param for testing (only in non-production)
        if (app()->environment('local', 'staging') && $request->query('_country')) {
            return strtoupper($request->query('_country'));
        }

        if (SystemSetting::get('geo_detection_enabled', true)) {
            return $this->geo->getCountryCode($request);
        }

        return 'US';
    }

    private function resolveLocale(Request $request, string $country): string
    {
        // 1. Cookie override (set explicitly by the visitor via the language switcher)
        if (SystemSetting::get('allow_manual_language_switch', true)) {
            $cookieLocale = $request->cookie('velora_locale_override');
            if ($cookieLocale && in_array($cookieLocale, GeoService::SUPPORTED_LOCALES, true)) {
                return $cookieLocale;
            }
        }

        // 2. Session persistence (returning visitor same session, only if they picked one)
        if (Session::has('central_locale')) {
            return Session::get('central_locale');
        }

        // 3. English is the default/official language for every new visitor,
        //    regardless of detected country. Language is now only ever changed
        //    when the visitor explicitly picks one from the language switcher.
        return 'en';
    }

    private function resolveCurrency(Request $request, string $country): string
    {
        // 1. Cookie override (set by currency switcher)
        if (SystemSetting::get('allow_manual_currency_switch', true)) {
            $cookieCurrency = $request->cookie('velora_currency_override');
            if ($cookieCurrency && strlen($cookieCurrency) === 3) {
                return strtoupper($cookieCurrency);
            }
        }

        // 2. Session persistence
        if (Session::has('current_currency')) {
            return Session::get('current_currency');
        }

        // 3. Geo detection
        return $this->geo->getCurrencyForCountry($country);
    }
}
