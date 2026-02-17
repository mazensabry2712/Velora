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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roles
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Load user role if not loaded
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        // Split roles by pipe (|) to allow multiple roles
        $allowedRoles = explode('|', $roles);

        // Check if user has any of the required roles
        $userRole = $user->role?->name;

        if (!$userRole || !in_array($userRole, $allowedRoles)) {
            return redirect()->route('customer.booking')->with('error', 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
