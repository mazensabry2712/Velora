<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenBelongsToTenant
{
    /**
     * Ensure an authenticated Sanctum token is explicitly scoped to the
     * tenant that was initialized for the current request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = tenant();
        $token = $user?->currentAccessToken();

        if (!$user || !$tenant || !$token) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'A valid tenant access token is required.',
            ], 401);
        }

        $requiredAbility = 'tenant:' . $tenant->id;

        if (!$token->can($requiredAbility)) {
            return response()->json([
                'error' => 'Tenant mismatch',
                'message' => 'The access token is not authorized for this tenant.',
            ], 403);
        }

        return $next($request);
    }
}
