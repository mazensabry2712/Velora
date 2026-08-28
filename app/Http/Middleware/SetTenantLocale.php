<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetTenantLocale
{
    /**
     * Resolve the tenant UI locale with this precedence:
     * explicit query parameter -> current session -> tenant default -> app default.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLanguages = array_values(array_unique(
            config('localizer.supported_locales', ['en', 'ar'])
        ));

        try {
            $tenant = tenant();

            if ($tenant) {
                $settingsModel = \App\Models\Setting::where('tenant_id', $tenant->id)->first();

                if ($settingsModel?->available_languages) {
                    $available = is_string($settingsModel->available_languages)
                        ? json_decode($settingsModel->available_languages, true)
                        : $settingsModel->available_languages;

                    if (is_array($available) && $available !== []) {
                        $supportedLanguages = array_values(array_intersect(
                            $supportedLanguages,
                            $available
                        ));
                    }
                }
            }
        } catch (\Throwable) {
            // Keep central configured locales as the fallback.
        }

        if ($supportedLanguages === []) {
            $supportedLanguages = ['en'];
        }

        $locale = null;
        $requestedLocale = $request->query('lang');

        if (is_string($requestedLocale) && in_array($requestedLocale, $supportedLanguages, true)) {
            $locale = $requestedLocale;
            session()->put('locale', $locale);
        } elseif (session()->has('locale') && in_array(session('locale'), $supportedLanguages, true)) {
            $locale = session('locale');
        } else {
            try {
                $tenant = tenant();
                $tenantDefault = $tenant?->settings?->language;

                if (is_string($tenantDefault) && in_array($tenantDefault, $supportedLanguages, true)) {
                    $locale = $tenantDefault;
                    session()->put('locale', $locale);
                }
            } catch (\Throwable) {
                // Fall through to application default.
            }
        }

        $locale ??= in_array(config('app.locale', 'en'), $supportedLanguages, true)
            ? config('app.locale', 'en')
            : $supportedLanguages[0];

        App::setLocale($locale);

        try {
            $tenantSubdomain = null;

            if (function_exists('tenant') && tenant()) {
                if (isset(tenant()->subdomain) && tenant()->subdomain) {
                    $tenantSubdomain = tenant()->subdomain;
                }
            }

            if (! $tenantSubdomain) {
                $host = $request->getHost();
                $base = env('APP_BASE_DOMAIN', 'velora.test');

                if ($base && str_ends_with($host, $base)) {
                    $tenantSubdomain = preg_replace(
                        '/\.' . preg_quote($base, '/') . '$/i',
                        '',
                        $host
                    );
                } else {
                    $parts = explode('.', $host);
                    $tenantSubdomain = $parts[0] ?? null;
                }
            }

            if ($tenantSubdomain) {
                \Illuminate\Support\Facades\URL::defaults([
                    'tenantSubdomain' => $tenantSubdomain,
                ]);
            }
        } catch (\Throwable) {
            // URL defaults are best-effort only.
        }

        return $next($request);
    }
}
