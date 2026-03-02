<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Services\GeoService;
use Illuminate\Support\Facades\DB;

class LandingController extends Controller
{
    public function __construct(private GeoService $geo) {}

    /**
     * Show the main landing page.
     */
    public function index()
    {
        $countryCode = session('detected_country', 'US');
        $currency    = session('current_currency', 'USD');
        $plans       = $this->geo->getPlansForCountry($countryCode);
        $stats       = $this->getPlatformStats();
        $maxTrialDays = $plans->max('trial_days') ?? 14;

        return view('landing.index', compact('plans', 'stats', 'maxTrialDays', 'countryCode', 'currency'));
    }

    /**
     * Show dedicated pricing page.
     */
    public function pricing()
    {
        $countryCode  = session('detected_country', 'US');
        $currency     = session('current_currency', 'USD');
        $plans        = $this->geo->getPlansForCountry($countryCode);
        $maxTrialDays = $plans->max('trial_days') ?? 14;

        return view('landing.pricing', compact('plans', 'maxTrialDays', 'countryCode', 'currency'));
    }

    /**
     * Show signup form.
     */
    public function signup()
    {
        $countryCode  = session('detected_country', 'US');
        $plans        = $this->geo->getPlansForCountry($countryCode);
        $maxTrialDays = $plans->max('trial_days') ?? 14;

        return view('landing.signup', compact('plans', 'maxTrialDays', 'countryCode'));
    }

    /**
     * Check subdomain availability via AJAX.
     */
    public function checkSubdomain(Request $request)
    {
        $request->validate(['subdomain' => 'required|string|min:3|max:32']);

        $service = app(\App\Services\TenantRegistrationService::class);
        $result  = $service->checkSubdomainAvailability($request->subdomain);

        return response()->json($result);
    }

    /**
     * Get basic platform stats for the landing page.
     */
    private function getPlatformStats(): array
    {
        try {
            return [
                'tenants'      => DB::table('tenants')->count(),
                'appointments' => 0, // Aggregate across tenant DBs (optional)
                'countries'    => DB::table('tenants')
                    ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.country')) as country")
                    ->whereRaw("JSON_EXTRACT(data, '$.country') IS NOT NULL")
                    ->distinct()
                    ->count(),
            ];
        } catch (\Exception $e) {
            return ['tenants' => '500+', 'appointments' => '50,000+', 'countries' => '30+'];
        }
    }
}
