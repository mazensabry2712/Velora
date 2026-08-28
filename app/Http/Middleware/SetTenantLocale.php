<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetTenantLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // The UI locale registry is global and must match the languages exposed
        // by the Landing. Tenant `available_languages` may govern business or
        // booking content, but it must not prevent the application UI from
        // using any supported platform language.
        $supportedLanguages = array_values(array_unique(
            config('localizer.supported_locales', ['ar', 'en'])
        ));

        if ($supportedLanguages === []) {
            $supportedLanguages = ['ar'];
        }

        $locale = null;

        // A signed-in tenant user gets the persistent preference first.
        // This outranks session state so the preference survives logout/login
        // and cannot silently revert to the Tenant default.
        try {
            $user = $request->user();
            $userLocale = $user?->locale;

            if (is_string($userLocale) && in_array($userLocale, $supportedLanguages, true)) {
                $locale = $userLocale;
                session()->put('locale', $locale);
            }
        } catch (\Throwable) {
            // Continue with request/session/tenant resolution.
        }

        $requestedLocale = $request->query('lang');
        if ($locale === null && is_string($requestedLocale) && in_array($requestedLocale, $supportedLanguages, true)) {
            $locale = $requestedLocale;
            session()->put('locale', $locale);
        }

        if ($locale === null && session()->has('locale') && in_array(session('locale'), $supportedLanguages, true)) {
            $locale = session('locale');
        }

        if ($locale === null) {
            try {
                $tenant = tenant();
                $tenantDefault = $tenant?->language;

                if (! is_string($tenantDefault) || ! in_array($tenantDefault, $supportedLanguages, true)) {
                    $tenantDefault = $tenant?->settings?->language;
                }

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
