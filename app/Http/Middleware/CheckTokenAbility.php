<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenAbility
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$abilities
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Authentication required'
            ], 401);
        }

        // Check if token has required abilities
        $token = $user->currentAccessToken();

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid token'
            ], 401);
        }

        // Check each ability
        foreach ($abilities as $ability) {
            if (!$token->can($ability)) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Token does not have required ability: ' . $ability
                ], 403);
            }
        }

        return $next($request);
    }
}
