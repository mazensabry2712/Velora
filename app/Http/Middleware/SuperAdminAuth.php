<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminAuth
{
    /**
     * Handle an incoming request - Super Admin authentication and authorization
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->guard('web')->check()) {
            // If API request, return JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'Please login to access this resource'
                ], 401);
            }

            // If web request, redirect to Super Admin login
            return redirect()->route('super-admin.login')
                ->with('error', 'يجب تسجيل الدخول أولاً للوصول إلى هذه الصفحة');
        }

        $user = auth()->guard('web')->user();

        // Check if user has Super Admin role
        if (!$user->isSuperAdmin()) {
            // If API request, return JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Super Admin access required'
                ], 403);
            }

            // If web request, abort with 403
            abort(403, 'يجب أن تكون Super Admin للوصول إلى هذه الصفحة');
        }

        return $next($request);
    }
}
