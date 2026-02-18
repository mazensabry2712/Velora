<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SuperAdminAuthController extends Controller
{
    /**
     * Super Admin Login (Central Authentication)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Find Super Admin
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user has Super Admin role
        if (!$user->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'email' => ['You do not have Super Admin access.'],
            ]);
        }

        // Create token
        $token = $user->createToken('super-admin-token', ['super-admin'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Super Admin logged in successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'Super Admin',
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * Get Super Admin Profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'Super Admin',
                'permissions' => $user->permissions ?? [],
            ]
        ]);
    }

    /**
     * Super Admin Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Super Admin Web Login (for Blade forms)
     */
    public function webLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Find Super Admin
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'البيانات المدخلة غير صحيحة.',
            ])->withInput();
        }

        // Check if user has Super Admin role
        if (!$user->isSuperAdmin()) {
            return back()->withErrors([
                'email' => 'ليس لديك صلاحية Super Admin.',
            ])->withInput();
        }

        // Login user
        auth()->guard('web')->login($user, $request->filled('remember'));

        $request->session()->regenerate();

        return redirect()->route('super-admin.dashboard');
    }

    /**
     * Super Admin Web Logout
     */
    public function webLogout(Request $request)
    {
        auth()->guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('super-admin.login');
    }
}
