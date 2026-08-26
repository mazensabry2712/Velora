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
     * responses. Legacy/admin web endpoints keep their redirect behaviour.
     *
     * @param  Closure(\Illuminate\Http\Request): (Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roles
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();
        $isVersionedApi = $request->is('api/v1/*') || $request->routeIs('api.v1.*');

        if (!$user) {
            if ($isVersionedApi && $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->route('login');
        }

        // Split roles by pipe (|) to allow multiple roles.
        $allowedRoles = explode('|', $roles);

        // Spatie Permission is the single source of truth for roles.
        $userRole = $user->getRoleNames()->first();

        if (!$userRole || !in_array($userRole, $allowedRoles, true)) {
            if ($isVersionedApi && $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this resource.',
                ], 403);
            }

            return redirect()->route('customer.booking')->with(
                'error',
                'You do not have permission to access this page.'
            );
        }

        return $next($request);
    }
}
