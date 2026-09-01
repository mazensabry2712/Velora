<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\CountryPricing;
use App\Models\CountryTax;
use App\Models\CountrySetting;
use App\Services\GeoService;
use App\Services\PricingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

class LandingController extends Controller
{
    public function __construct(
        private GeoService $geo,
        private PricingService $pricing,
    ) {}

    /**
     * Show the main landing page.
     */
    public function index(Request $request)
    {
        $countryCode  = session('detected_country', 'US');
        $plans        = $this->geo->getPlansForCountry($countryCode);
        $stats        = $this->getPlatformStats();
        $maxTrialDays = $plans->max('trial_days') ?? 14;

        try {
            $pricing = $this->pricing->getPricingSummary($request);
            $taxRecord = CountryTax::forCountry($pricing['country_code']);
            $taxPct = (float) ($taxRecord?->tax_percentage ?? 0);
            $taxName = $taxRecord?->tax_name ?? 'VAT';
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('LandingController pricing/service failed early: ' . $e->getMessage());
            $pricing = [
                'country_code'    => 'GLOBAL',
                'country_name'    => 'Other countries',
                'price'           => 0,
                'currency'        => 'USD',
                'formatted_price' => 'USD 0',
                'payment_methods' => [],
                'is_global'       => true,
            ];
            $taxPct = 0;
            $taxName = 'VAT';
        }

        try {
            $allPricing = CountryPricing::active()
                ->orderByRaw("CASE WHEN country_code = 'GLOBAL' THEN 1 ELSE 0 END")
                ->orderBy('country_name')
                ->get();

            $globalPricing = $allPricing->firstWhere('country_code', 'GLOBAL') ?? CountryPricing::global();
            $countriesWithPricing = $allPricing->where('country_code', '!=', 'GLOBAL')->sortBy('country_name');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('LandingController pricing load failed: ' . $e->getMessage());
            $globalPricing = new CountryPricing([
                'country_code' => 'GLOBAL',
                'country_name' => 'Other countries',
                'price'        => 0,
                'currency'     => 'USD',
                'payment_methods' => [],
                'is_active'    => true,
            ]);
            $allPricing = collect([$globalPricing]);
            $countriesWithPricing = collect();
        }

        $allDataJson = json_encode(
            collect($allPricing)->mapWithKeys(function ($cp) {
                $taxRec  = CountryTax::forCountry($cp->country_code);
                $setting = CountrySetting::getByCode($cp->country_code);
                return [
                    $cp->country_code => [
                        'name'     => $cp->country_name,
                        'monthly'  => $cp->formattedPrice(),
                        'price'    => (float) $cp->price,
                        'currency' => $cp->currency,
                        'methods'  => $cp->payment_methods ?? [],
                        'taxPct'   => (float) ($taxRec?->tax_percentage ?? 0),
                        'taxName'  => $taxRec?->tax_name ?? 'VAT',
                        'lang'     => $setting?->default_language ?? 'en',
                    ],
                ];
            })
        );

        $globalDataJson = json_encode([
            'name'     => 'Other countries',
            'monthly'  => $globalPricing->formattedPrice(),
            'price'    => (float) $globalPricing->price,
            'currency' => $globalPricing->currency,
            'methods'  => $globalPricing->payment_methods ?? [],
            'taxPct'   => 0,
            'taxName'  => 'VAT',
            'lang'     => 'en',
        ]);

        $trialDays           = (int) SystemSetting::get('default_trial_days', 14);
        $appName             = SystemSetting::get('app_name', config('app.name', 'Velora'));
        $registrationEnabled = (bool) SystemSetting::get('registration_enabled', true);
        $currentLocale       = session('central_locale', 'en');

        // Ensure tenant route generation from the central landing doesn't
        // throw UrlGenerationException when views call tenant routes
        // (e.g. `route('queue.status')`). Default to the demo subdomain
        // seeded in development so links work from the marketing site.
        try {
            \Illuminate\Support\Facades\URL::defaults(['tenantSubdomain' => 'demo']);
        } catch (\Exception $_) {
            // ignore
        }

        return view('landing.index', compact(
            'plans', 'stats', 'maxTrialDays', 'countryCode',
            'pricing', 'taxPct', 'taxName', 'trialDays', 'appName',
            'allPricing', 'globalPricing', 'countriesWithPricing',
            'allDataJson', 'globalDataJson',
            'registrationEnabled', 'currentLocale'
        ));
    }

    /**
     * Show signup form.
     */
    public function signup(Request $request)
    {
        $supported = config('localizer.supported_locales', []);
        $configuredDefault = config('localizer.omitted_locale', config('app.locale', 'en'));
        $default = $configuredDefault;

        try {
            $systemDefault = SystemSetting::get('public_default_locale', $configuredDefault);
            if (is_string($systemDefault) && in_array($systemDefault, $supported, true)) {
                $default = $systemDefault;
            }
        } catch (\Throwable) {
            // Keep the configured fallback when central settings are unavailable.
        }

        // An explicit locale in the signup URL is authoritative. This guard
        // runs at the controller boundary as the final source of truth after
        // Localizer middleware has completed, preventing a persisted default
        // locale from leaking into /{locale}/signup requests.
        $path = trim($request->path(), '/');
        $firstSegment = $path === '' ? null : explode('/', $path, 2)[0];
        $routeLocale = $request->route('locale');
        $explicitLocale = is_string($routeLocale) && in_array($routeLocale, $supported, true)
            ? $routeLocale
            : (is_string($firstSegment) && in_array($firstSegment, $supported, true)
                ? $firstSegment
                : null);

        if ($explicitLocale !== null) {
            App::setLocale($explicitLocale);
            session()->put('central_locale', $explicitLocale);
        } else {
            $sessionLocale = session('central_locale');
            $current = is_string($sessionLocale) && in_array($sessionLocale, $supported, true)
                ? $sessionLocale
                : (app()->getLocale() ?: $default);

            if (is_string($current) && in_array($current, $supported, true)) {
                App::setLocale($current);
                session()->put('central_locale', $current);
            }
        }

        if (! SystemSetting::get('registration_enabled', true)) {
            $appName = SystemSetting::get('app_name', config('app.name', 'Velora'));
            return response()->view('landing.registration-disabled', compact('appName'), 403);
        }

        $countryCode  = session('detected_country', 'US');
        $plans        = $this->geo->getPlansForCountry($countryCode);
        $maxTrialDays = $plans->max('trial_days') ?? SystemSetting::get('default_trial_days', 14);
        $landingLocale = app()->getLocale() ?: config('localizer.omitted_locale', config('app.locale', 'en'));

        return view('landing.signup', compact('plans', 'maxTrialDays', 'countryCode', 'landingLocale'));
    }

    /**
     * Show the dedicated pricing page.
     */
    public function pricing(Request $request)
    {
        $summary     = $this->pricing->getPricingSummary($request);
        $allPricing  = CountryPricing::active()
            ->orderByRaw("CASE WHEN country_code = 'GLOBAL' THEN 1 ELSE 0 END")
            ->orderBy('country_name')
            ->get();

        $globalPricing = $allPricing->firstWhere('country_code', 'GLOBAL')
            ?? CountryPricing::global();

        // Build tax info for the detected country
        $countryCode = $summary['country_code'];
        $taxRecord   = CountryTax::forCountry($countryCode);
        $taxPct      = (float) ($taxRecord?->tax_percentage ?? 0);
        $taxName     = $taxRecord?->tax_name ?? 'VAT';

        // Annual discount: pay 10 months, get 12
        $annualMultiplier   = 10;
        $trialDays          = (int) SystemSetting::get('default_trial_days', 14);
        $registrationEnabled = (bool) SystemSetting::get('registration_enabled', true);
        $appName            = SystemSetting::get('app_name', config('app.name', 'Velora'));

        return view('landing.pricing', [
            'pricing'             => $summary,
            'allPricing'          => $allPricing,
            'globalPricing'       => $globalPricing,
            'taxPct'              => $taxPct,
            'taxName'             => $taxName,
            'annualMultiplier'    => $annualMultiplier,
            'trialDays'           => $trialDays,
            'registrationEnabled' => $registrationEnabled,
            'appName'             => $appName,
        ]);
    }
