<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;

class TenantAuthController extends Controller
{
    public function login(Request $request)
    {
        $throttleKey = 'login:' . $request->ip() . ':' . $request->input('email');

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => [__('auth.throttle', ['seconds' => $seconds])],
            ]);
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $tenant = tenant();

        if (! $tenant) {
            return response()->json([
                'error' => __('auth.failed'),
                'message' => __('auth.failed'),
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->email_verified_at === null) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        RateLimiter::clear($throttleKey);

        $roleName = $user->getRoleNames()->first();
        $abilities = match ($roleName) {
            'Admin Tenant' => ['admin-tenant'],
            'Assistant' => ['assistant'],
            'Staff' => ['staff'],
            'Customer' => ['customer'],
            default => [],
        };

        $abilities[] = 'tenant:' . $tenant->id;

        auth()->login($user, $request->filled('remember'));

        $locale = $this->resolveUserLocale($user, $tenant);
        $user->forceFill(['locale' => $locale])->save();
        session()->put('locale', $locale);
        App::setLocale($locale);

        $token = $user->createToken('tenant-token', $abilities)->plainTextToken;

        $redirectTo = $roleName === 'Customer' ? '/my-queue' : '/admin/dashboard';

        return response()->json([
            'success' => true,
            'message' => __('messages.login_success'),
            'redirect_to' => $redirectTo,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'locale' => $locale,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $roleName,
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domains->first()?->domain ?? '',
            ],
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $tenant = tenant();

        if (! $tenant) {
            return response()->json([
                'error' => __('auth.failed'),
                'message' => __('auth.failed'),
            ], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'locale' => $this->resolveUserLocale(null, $tenant),
        ]);

        $user->assignRole('Customer');

        $token = $user->createToken('tenant-token', [
            'customer',
            'tenant:' . $tenant->id,
        ])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => __('messages.login_success'),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'locale' => $user->locale,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'Customer',
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domains->first()?->domain ?? '',
            ],
        ], 201);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $tenant = tenant();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale,
                'role' => $user->getRoleNames()->first(),
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
            ]
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => __('messages.logout'),
        ]);
    }

    private function resolveUserLocale(?User $user, $tenant): string
    {
        $supported = array_values(array_unique(config('localizer.supported_locales', ['ar', 'en'])));
        if ($user && is_string($user->locale) && in_array($user->locale, $supported, true)) {
            return $user->locale;
        }

        $tenantLocale = $tenant?->language;
        if (! is_string($tenantLocale) || ! in_array($tenantLocale, $supported, true)) {
            $tenantLocale = $tenant?->settings?->language;
        }

        if (is_string($tenantLocale) && in_array($tenantLocale, $supported, true)) {
            return $tenantLocale;
        }

        $publicDefault = SystemSetting::get('public_default_locale', 'ar');
        return is_string($publicDefault) && in_array($publicDefault, $supported, true)
            ? $publicDefault
            : ($supported[0] ?? 'ar');
    }
}
