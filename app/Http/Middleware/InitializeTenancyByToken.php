<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyByToken
{
    public function __construct(
        protected Tenancy $tenancy,
    ) {}

    /**
     * Initialize the tenant before Sanctum authenticates the tenant user.
     *
     * Tenant selection and authentication are deliberately separate concerns:
     * the explicit tenant identifier only selects the tenant database, while
     * auth:sanctum authenticates the bearer token inside that database.
     * A following middleware validates the token's tenant scope.
     *
     * Login/register may provide the tenant id because no bearer token exists
     * yet. Authenticated API requests must provide it explicitly as well.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID') ?? $request->input('tenant_id');

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
}
