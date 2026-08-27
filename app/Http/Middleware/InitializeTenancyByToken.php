<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Stancl\Tenancy\Tenancy;
use App\Models\Tenant;

class InitializeTenancyByToken
{
    public function __construct(
        protected Tenancy $tenancy,
    ) {}

    /**
     * Initialize the tenant before Sanctum authenticates the tenant user.
     *
     * For authenticated API requests the tenant is derived from the central
     * Sanctum token record, never from a user-controlled tenant id/header.
     * Login/register may provide an explicit tenant id because no bearer token
     * exists yet.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if ($bearerToken) {
            $tenantId = $this->tenantIdFromBearerToken($bearerToken);

            if (!$tenantId) {
                return response()->json([
                    'error' => 'Invalid tenant token',
                    'message' => 'The access token is missing a valid tenant scope.',
                ], 401);
            }

            $requestedTenantId = $request->header('X-Tenant-ID') ?? $request->input('tenant_id');

            if ($requestedTenantId !== null && (string) $requestedTenantId !== (string) $tenantId) {
                return response()->json([
                    'error' => 'Tenant mismatch',
                    'message' => 'The requested tenant does not match the access token tenant.',
                ], 403);
            }
        } else {
            $tenantId = $request->header('X-Tenant-ID')
                ?? $request->input('tenant_id');
        }

        if (!$tenantId) {
            return response()->json([
                'error' => 'Tenant identifier is required',
                'message' => 'Please provide tenant_id in header X-Tenant-ID or as parameter',
            ], 400);
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant not found',
                'message' => 'Invalid tenant identifier',
            ], 404);
        }

        if (!$tenant->active) {
            return response()->json([
                'error' => 'Tenant inactive',
                'message' => 'This tenant account is not active',
            ], 403);
        }

        $this->tenancy->initialize($tenant);

        return $next($request);
    }

    private function tenantIdFromBearerToken(string $bearerToken): ?string
    {
        [$tokenId] = explode('|', $bearerToken, 2);

        if ($tokenId === '' || !ctype_digit($tokenId)) {
            return null;
        }

        $token = DB::connection('mysql')
            ->table('personal_access_tokens')
            ->where('id', (int) $tokenId)
            ->where('token', hash('sha256', $bearerToken))
            ->first(['abilities']);

        if (!$token) {
            return null;
        }

        $abilities = json_decode($token->abilities ?? '[]', true);

        if (!is_array($abilities)) {
            return null;
        }

        foreach ($abilities as $ability) {
            if (is_string($ability) && str_starts_with($ability, 'tenant:')) {
                $tenantId = substr($ability, strlen('tenant:'));

                return $tenantId !== '' ? $tenantId : null;
            }
        }

        return null;
    }
}
