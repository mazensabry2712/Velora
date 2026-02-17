<?php

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
        // Rate limiting for login attempts
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

        // Get current tenant
        $tenant = tenant();

        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant not initialized',
                'message' => 'Please access via valid tenant domain or provide tenant identifier'
            ], 400);
        }

        // Find user in current tenant database
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60); // 1 minute decay
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($throttleKey);

        // Get user role
        $role = $user->role;
        $abilities = [];

        // Set token abilities based on role
        if ($role) {
            switch ($role->name) {
                case 'Admin Tenant':
                    $abilities = ['admin-tenant'];
                    break;
                case 'Assistant':
                    $abilities = ['assistant'];
                    break;
                case 'Staff':
                    $abilities = ['staff'];
                    break;
                case 'Customer':
                    $abilities = ['customer'];
                    break;
            }
        }

        // Login user in session for web
        auth()->login($user, $request->filled('remember'));

        // Create token with abilities for API
        $token = $user->createToken('tenant-token', $abilities)->plainTextToken;

        // Determine redirect URL based on role
        $redirectTo = '/admin/dashboard';
        if ($role && $role->name === 'Customer') {
            $redirectTo = '/my-queue';
        }

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
                'role' => $role?->name,
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

        // Get current tenant
        $tenant = tenant();

        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant not initialized',
                'message' => 'Please access via valid tenant domain or provide tenant identifier'
            ], 400);
        }

        // Create user with Customer role
        $customerRole = \App\Models\Role::where('name', 'Customer')->first();

        $user = User::create([
            'role_id' => $customerRole?->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create token
        $token = $user->createToken('tenant-token', ['customer'])->plainTextToken;

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
                'role' => $user->role?->name,
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
        // Delete current access token if exists
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        // Logout from session
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
