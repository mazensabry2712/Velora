<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class TenantAuthController extends Controller
{
    /**
     * Tenant User Login
     */
    public function login(Request $request)
    {
        $throttleKey = 'login:' . $request->ip() . ':' . $request->input('email');

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ]);
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $tenant = tenant();

        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant not initialized',
                'message' => 'Please access via valid tenant domain or provide tenant identifier',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->email_verified_at === null) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'email' => ['Please verify your email address before signing in.'],
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

        $token = $user->createToken('tenant-token', $abilities)->plainTextToken;

        $redirectTo = $roleName === 'Customer' ? '/my-queue' : '/admin/dashboard';

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully',
            'redirect_to' => $redirectTo,
            'access_token' => $token,
            'token_type' => 'Bearer',
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

    /**
     * Register new Customer
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $tenant = tenant();

        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant not initialized',
                'message' => 'Please access via valid tenant domain or provide tenant identifier',
            ], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('Customer');

        $token = $user->createToken('tenant-token', [
            'customer',
            'tenant:' . $tenant->id,
        ])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully',
            'access_token' => $token,
            'token_type' => 'Bearer',
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

    /**
     * Get Tenant User Profile
     */
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
                'role' => $user->getRoleNames()->first(),
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
            ]
        ]);
    }

    /**
     * Tenant User Logout
     */
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
            'message' => 'Logged out successfully',
        ]);
    }
}
