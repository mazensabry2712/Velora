<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            // If API request, return JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Authentication required'
                ], 401);
            }
            // If web request, redirect to login
            return redirect()->route('super-admin.login');
        }

        // Check if user has Super Admin role
        if (!$user->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Super Admin access required'
                ], 403);
            }
            abort(403, 'Super Admin access required');
        }

        return $next($request);
    }
}
