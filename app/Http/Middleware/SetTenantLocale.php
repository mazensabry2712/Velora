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
        // Default supported languages (fallback)
        $supportedLanguages = ['en', 'ar', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja'];

        // Get available languages from tenant settings
        try {
            $tenant = tenant();
            if ($tenant) {
                $settingsModel = \App\Models\Setting::where('tenant_id', $tenant->id)->first();
                if ($settingsModel && $settingsModel->available_languages) {
                    $supportedLanguages = $settingsModel->available_languages;
                }
            }
        } catch (\Exception $e) {
            // Use default if error
        }

        // Check if language is passed in URL
        if ($request->has('lang') && in_array($request->query('lang'), $supportedLanguages)) {
            $locale = $request->query('lang');
            session()->put('locale', $locale);
            App::setLocale($locale);
        }
        // Check if user has selected a language in session
        elseif (session()->has('locale') && in_array(session('locale'), $supportedLanguages)) {
            $locale = session('locale');
            App::setLocale($locale);
        } else {
            // Fall back to tenant settings or default
            try {
                $tenant = tenant();

                if ($tenant && isset($tenant->settings) && isset($tenant->settings->language)) {
                    App::setLocale($tenant->settings->language);
                } else {
                    // Use first available language or 'en'
                    App::setLocale($supportedLanguages[0] ?? 'en');
                }
            } catch (\Exception $e) {
                App::setLocale(config('app.locale', 'en'));
            }
        }
                // Ensure URL generation receives tenant subdomain by default
                try {
                    $tenantSubdomain = null;
                    if (function_exists('tenant') && tenant()) {
                        // Prefer explicit property if present
                        if (isset(tenant()->subdomain) && tenant()->subdomain) {
                            $tenantSubdomain = tenant()->subdomain;
                        }
                    }

                    // Fallback: extract subdomain from host (demo.velora.test -> demo)
                    if (! $tenantSubdomain) {
                        $host = $request->getHost();
                        $base = env('APP_BASE_DOMAIN', 'velora.test');
                        if ($base && str_ends_with($host, $base)) {
                            $tenantSubdomain = preg_replace('/\.' . preg_quote($base, '/') . '$/i', '', $host);
                        } else {
                            $parts = explode('.', $host);
                            $tenantSubdomain = $parts[0] ?? null;
                        }
                    }

                    if ($tenantSubdomain) {
                        \Illuminate\Support\Facades\URL::defaults(['tenantSubdomain' => $tenantSubdomain]);
                    }
                } catch (\Exception $e) {
                    // ignore
                }

        return $next($request);
    }
}
