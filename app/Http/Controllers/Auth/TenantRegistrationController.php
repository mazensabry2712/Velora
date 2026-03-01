<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TenantRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantRegistrationController extends Controller
{
    public function __construct(
        protected TenantRegistrationService $registrationService
    ) {}

    /**
     * Handle new tenant signup.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|min:2|max:100',
            'subdomain'     => 'required|string|min:3|max:32|regex:/^[a-z0-9][a-z0-9\-]{1,30}[a-z0-9]$/',
            'email'         => 'required|email:rfc,dns|max:191',
            'password'      => 'required|string|min:8|confirmed',
            'country'       => 'nullable|string|size:2',
            'language'      => 'nullable|string|in:en,ar,fr,es,de,it,pt,ru,zh,ja',
            'terms'         => 'required|accepted',
            'plan_id'       => 'nullable|integer|exists:subscription_plans,id',
        ], [
            'subdomain.regex'    => 'Subdomain must be lowercase letters, numbers, or hyphens only.',
            'terms.accepted'     => 'You must accept the Terms of Service.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        try {
            $result = $this->registrationService->register($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success'      => true,
                    'redirect_url' => $result['redirect_url'],
                    'message'      => 'Account created! Redirecting to your dashboard...',
                ]);
            }

            return redirect()->away($result['redirect_url'])
                ->with('success', 'Welcome to Velora! Your ' . ($result['trial_days'] ?? 14) . '-day free trial has started.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $e->errors(),
                ], 422);
            }
            throw $e;

        } catch (\Exception $e) {
            Log::error('Tenant registration error: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again.',
                ], 500);
            }

            return back()->withInput()->withErrors([
                'general' => 'Something went wrong. Please try again or contact support.',
            ]);
        }
    }
}
