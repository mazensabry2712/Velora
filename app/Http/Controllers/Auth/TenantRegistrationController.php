<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Application\Tenant\Actions\RegisterTenant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class TenantRegistrationController extends Controller
{
    public function __construct(
        private readonly RegisterTenant $registerTenant,
    ) {}

    /**
     * Handle new tenant signup.
     *
     * HTTP validation/response concerns stay here; tenant onboarding
     * orchestration is owned by the application action.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|min:2|max:100',
            'business_type' => 'nullable|string|max:60',
            'subdomain'     => 'required|string|min:3|max:32|regex:/^[a-z0-9][a-z0-9\-]{1,30}[a-z0-9]$/',
            'email'         => 'required|email:rfc,dns|max:191',
            'password'      => 'required|string|min:8|confirmed',
            'country'       => 'nullable|string|size:2',
            'language'      => 'nullable|string|in:en,ar,fr,es,de,it,pt,ru,zh,ja,tr,hi,ko,nl,id',
            'terms'         => 'required|accepted',
            'plan_id'       => 'nullable|integer|exists:subscription_plans,id',
        ], [
            'subdomain.regex'     => 'Subdomain must be lowercase letters, numbers, or hyphens only.',
            'terms.accepted'      => 'You must accept the Terms of Service.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        try {
            $result = $this->registerTenant->execute($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success'      => true,
                    'redirect_url' => $result['redirect_url'],
                    'message'      => 'Account created! Redirecting to your dashboard...',
                ]);
            }

            return redirect()->away($result['redirect_url'])
                ->with(
                    'success',
                    'Welcome to Velora! Your ' . ($result['trial_days'] ?? 14) . '-day free trial has started.'
                );
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $e->errors(),
                ], 422);
            }

            return redirect()->to($this->signupUrl($request))
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Tenant registration error: ' . $e->getMessage(), [
                'email'     => $request->input('email'),
                'subdomain' => $request->input('subdomain'),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong. Please try again.',
                ], 500);
            }

            return redirect()->to($this->signupUrl($request))
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([
                    'general' => 'Something went wrong. Please try again or contact support.',
                ]);
        }
    }

    private function signupUrl(Request $request): string
    {
        $locale = $request->route('locale');

        if (is_string($locale) && $locale !== '') {
            return url('/' . $locale . '/signup');
        }

        return url('/signup');
    }
}
