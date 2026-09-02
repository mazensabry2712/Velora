<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\StaffWorkingHours;
use App\Models\UsageLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OnboardingController extends Controller
{
    private function ensureTenantAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('Admin Tenant'), 403);
    }

    public function index()
    {
        $settings = Setting::first();
        if ($settings?->onboarding_completed) return redirect()->route('admin.dashboard');

        $currentStep = $settings?->onboarding_step ?? 0;
        $subdomain = tenant('id');
        $businessName = tenant()?->name ?? $settings?->business_name ?? '';
        $domain = config('app.base_domain', config('app.domain', 'velora.test'));
        $scheme = request()->secure() ? 'https' : 'http';
        $bookingUrl = "{$scheme}://{$subdomain}.{$domain}/book";

        return view('admin.onboarding.wizard-v2', compact('currentStep', 'bookingUrl', 'subdomain', 'domain', 'businessName'));
    }

    public function saveStep1(Request $request): JsonResponse
    {
        $this->ensureTenantAdmin();
        $data = $request->validate([
            'phone' => 'required|string|max:30',
            'address' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);

        try {
            $update = [
                'phone' => $data['phone'],
                'address' => $data['address'] ?? null,
                'onboarding_step' => 1,
            ];

            if ($request->hasFile('logo')) {
                $update['logo'] = $request->file('logo')->store('logos/' . tenant('id'), 'public');
            }

            Setting::updateOrCreate(['id' => 1], $update);
            UsageLog::log('onboarding_step1_completed', [
                'business_name' => tenant()?->name ?? Setting::first()?->business_name,
            ]);

            return response()->json(['success' => true, 'next_step' => 2]);
        } catch (\Exception $e) {
            Log::error('Onboarding step1: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Something went wrong.')], 500);
        }
    }

    public function saveStep2(Request $request): JsonResponse
    {
        $this->ensureTenantAdmin();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'specialty' => 'nullable|string|max:100',
        ]);

        try {
            $staff = Staff::query()->first();
            $title = filled($data['specialty'] ?? null)
                ? ['en' => trim($data['specialty']), 'ar' => trim($data['specialty'])]
                : null;

            if (! $staff) {
                $nameParts = explode(' ', trim($data['name']), 2);
                $staff = Staff::create([
                    'user_id' => auth()->id(),
                    'first_name' => $nameParts[0],
                    'last_name' => $nameParts[1] ?? '',
                    'email' => auth()->user()?->email,
                    'title' => $title,
                    'accepts_bookings' => true,
                    'is_active' => true,
                ]);
            } else {
                $updates = ['accepts_bookings' => true, 'is_active' => true];
                if ($staff->user_id === null && auth()->id()) $updates['user_id'] = auth()->id();
                if ($title !== null) $updates['title'] = $title;
                $staff->update($updates);
            }

            $this->ensureDefaultWorkingHours($staff);
            Setting::updateOrCreate(['id' => 1], ['onboarding_step' => 2]);
            UsageLog::log('onboarding_step2_completed', ['staff_name' => $data['name']]);

            return response()->json(['success' => true, 'next_step' => 3]);
        } catch (\Exception $e) {
            Log::error('Onboarding step2: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Something went wrong.')], 500);
        }
    }

    public function saveStep3(Request $request): JsonResponse
    {
        $this->ensureTenantAdmin();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'duration' => 'required|integer|min:5|max:480',
            'price' => 'required|numeric|min:0',
        ]);

        try {
            $service = Service::query()->first();

            if (! $service) {
                $service = Service::create([
                    'name' => $data['name'],
                    'name_ar' => $data['name'],
                    'duration' => $data['duration'],
                    'price' => $data['price'],
                    'is_active' => true,
                    'is_online_bookable' => true,
                ]);
            } else {
                $service->update([
                    'name' => $data['name'],
                    'duration' => $data['duration'],
                    'price' => $data['price'],
                    'is_active' => true,
                    'is_online_bookable' => true,
                ]);
            }

            $staff = Staff::query()->orderBy('id')->first();
            if ($staff) {
                $staff->services()->syncWithoutDetaching([$service->id]);
                $this->ensureDefaultWorkingHours($staff);
            }

            Setting::updateOrCreate(['id' => 1], ['onboarding_step' => 3]);
            UsageLog::log('onboarding_step3_completed', ['service_name' => $data['name']]);

            return response()->json(['success' => true, 'next_step' => 4]);
        } catch (\Exception $e) {
            Log::error('Onboarding step3: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Something went wrong.')], 500);
        }
    }

    public function complete(Request $request): JsonResponse
    {
        $this->ensureTenantAdmin();
        try {
            $staff = Staff::query()->first();
            $service = Service::query()->first();

            if (! $staff || ! $service) {
                return response()->json([
                    'success' => false,
                    'message' => __('Please complete the staff and service steps first.'),
                ], 422);
            }

            $staff->update(['is_active' => true, 'accepts_bookings' => true]);
            $service->update(['is_active' => true, 'is_online_bookable' => true]);
            $this->ensureDefaultWorkingHours($staff);
            $staff->services()->syncWithoutDetaching([$service->id]);

            Setting::updateOrCreate(['id' => 1], [
                'onboarding_step' => 4,
                'onboarding_completed' => true,
                'booking_enabled' => true,
                'queue_enabled' => true,
            ]);

            $this->markTrialActivated();
            UsageLog::log('onboarding_completed', []);

            return response()->json(['success' => true, 'redirect_url' => route('admin.dashboard')]);
        } catch (\Exception $e) {
            Log::error('Onboarding complete: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Something went wrong.')], 500);
        }
    }

    private function ensureDefaultWorkingHours(Staff $staff): void
    {
        foreach (range(0, 6) as $dayOfWeek) {
            StaffWorkingHours::updateOrCreate(
                ['staff_id' => $staff->id, 'day_of_week' => $dayOfWeek],
                ['start_time' => '09:00', 'end_time' => '17:00', 'is_working' => true]
            );
        }
    }

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
