<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

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
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Authentication required'
            ], 401);
        }

        // Super Admin should not have tenant_id (central user)
        if ($user->tenant_id !== null) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Super Admin access required'
            ], 403);
        }

        // Check if user has Super Admin role
        if (!$user->hasRole('Super Admin')) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Super Admin access required'
            ], 403);
        }

        return $next($request);
    }
}
