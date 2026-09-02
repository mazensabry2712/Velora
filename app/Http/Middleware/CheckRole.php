<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Versioned tenant API requests (`/api/v1/...`) receive JSON authorization
     * responses. Web endpoints use the application's standard authentication
     * and authorization flow.
     *
     * @param  Closure(\Illuminate\Http\Request): (Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roles
     */
    public function handle(Request $request, Closure $next, string $roles = 'Super Admin'): Response
    {
        $user = $request->user();
        $isVersionedApi = $request->is('api/v1/*') || $request->routeIs('api.v1.*');
        $isSuperAdminGuard = trim($roles) === 'Super Admin';

        if (! $user) {
            if ($isVersionedApi && $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->route($isSuperAdminGuard ? 'super-admin.login' : 'login');
        }

        $allowedRoles = array_values(array_filter(explode('|', $roles)));
        $hasAllowedRole = $allowedRoles !== [] && $user->hasAnyRole($allowedRoles);

        if (! $hasAllowedRole) {
            if ($isVersionedApi && $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this resource.',
                ], 403);
            }

            if ($isSuperAdminGuard) {
                abort(403, 'Super Admin access required');
            }

            return redirect()->route('customer.booking')->with(
                'error',
                'You do not have permission to access this page.'
            );
        }

        return $next($request);
    }
}
