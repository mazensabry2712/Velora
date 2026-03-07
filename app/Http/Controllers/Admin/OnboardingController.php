<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\UsageLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * OnboardingController — 4-step wizard, under 5 minutes.
 *
 * Step 1: Business info  (name, phone, logo)
 * Step 2: First staff    (name, specialty)
 * Step 3: First service  (name, duration, price)
 * Step 4: Booking link   (show subdomain + QR code)
 *
 * Tracks progress via settings.onboarding_step and settings.onboarding_completed.
 */
class OnboardingController extends Controller
{
    // ── Page ─────────────────────────────────────────────────────────────

    public function index()
    {
        $settings = Setting::first();

        // If already completed, redirect to dashboard
        if ($settings?->onboarding_completed) {
            return redirect()->route('admin.dashboard');
        }

        $currentStep = $settings?->onboarding_step ?? 0;
        $subdomain   = tenant('id');
        $domain      = config('app.domain', 'velora.app');
        $bookingUrl  = "https://{$subdomain}.{$domain}/book";

        return view('admin.onboarding.wizard', compact('currentStep', 'bookingUrl', 'subdomain', 'domain'));
    }

    // ── Step 1: Business Info ─────────────────────────────────────────────

    public function saveStep1(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:100',
            'phone'         => 'required|string|max:30',
            'address'       => 'nullable|string|max:255',
            'logo'          => 'nullable|image|max:2048',
        ]);

        try {
            $update = [
                'business_name'  => $data['business_name'],
                'phone'          => $data['phone'],
                'address'        => $data['address'] ?? null,
                'onboarding_step'=> 1,
            ];

            if ($request->hasFile('logo')) {
                $update['logo'] = $request->file('logo')
                    ->store('logos/' . tenant('id'), 'public');
            }

            Setting::updateOrCreate(['id' => 1], $update);

            UsageLog::log('onboarding_step1_completed', ['business_name' => $data['business_name']]);

            return response()->json(['success' => true, 'next_step' => 2]);
        } catch (\Exception $e) {
            Log::error('Onboarding step1: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Something went wrong.')], 500);
        }
    }

    // ── Step 2: First Staff ───────────────────────────────────────────────

    public function saveStep2(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'specialty'      => 'nullable|string|max:100',
        ]);

        try {
            // Only create if no staff exists yet
            if (Staff::count() === 0) {
                // Split name into first_name / last_name
                $nameParts = explode(' ', trim($data['name']), 2);
                Staff::create([
                    'first_name' => $nameParts[0],
                    'last_name'  => $nameParts[1] ?? '',
                    'is_active'  => true,
                ]);
            }

            Setting::updateOrCreate(['id' => 1], ['onboarding_step' => 2]);

            UsageLog::log('onboarding_step2_completed', ['staff_name' => $data['name']]);

            return response()->json(['success' => true, 'next_step' => 3]);
        } catch (\Exception $e) {
            Log::error('Onboarding step2: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Something went wrong.')], 500);
        }
    }

    // ── Step 3: First Service ─────────────────────────────────────────────

    public function saveStep3(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'duration' => 'required|integer|min:5|max:480',
            'price'    => 'required|numeric|min:0',
        ]);

        try {
            if (Service::count() === 0) {
                Service::create([
                    'name'      => $data['name'],
                    'name_ar'   => $data['name'],
                    'duration'  => $data['duration'],
                    'price'     => $data['price'],
                    'is_active' => true,
                ]);
            }

            Setting::updateOrCreate(['id' => 1], ['onboarding_step' => 3]);

            UsageLog::log('onboarding_step3_completed', ['service_name' => $data['name']]);

            return response()->json(['success' => true, 'next_step' => 4]);
        } catch (\Exception $e) {
            Log::error('Onboarding step3: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Something went wrong.')], 500);
        }
    }

    // ── Step 4: Complete — show booking link ──────────────────────────────

    public function complete(Request $request): JsonResponse
    {
        try {
            Setting::updateOrCreate(['id' => 1], [
                'onboarding_step'      => 4,
                'onboarding_completed' => true,
            ]);

            // Track activation in central DB
            $this->markTrialActivated();

            UsageLog::log('onboarding_completed', []);

            return response()->json([
                'success'     => true,
                'redirect_url'=> route('admin.dashboard'),
            ]);
        } catch (\Exception $e) {
            Log::error('Onboarding complete: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Something went wrong.')], 500);
        }
    }

    // ── Private ───────────────────────────────────────────────────────────

    private function markTrialActivated(): void
    {
        try {
            $central = config('tenancy.database.central_connection', 'mysql');
            DB::connection($central)
                ->table('tenant_subscriptions')
                ->where('tenant_id', tenant('id'))
                ->whereNull('activated_at')
                ->update(['activated_at' => now()]);
        } catch (\Exception $e) {
            Log::warning('Could not mark trial activated: ' . $e->getMessage());
        }
    }
}
