<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Stancl\Tenancy\Tenancy;
use App\Models\Tenant;

class InitializeTenancyByToken
{
    protected $tenancy;

    public function __construct(Tenancy $tenancy)
    {
        $this->tenancy = $tenancy;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for tenant identifier in request (token, header, or parameter)
        $tenantId = $request->header('X-Tenant-ID')
                    ?? $request->input('tenant_id')
                    ?? $request->bearerToken();

        if (!$tenantId) {
            return response()->json([
                'error' => 'Tenant identifier is required',
                'message' => 'Please provide tenant_id in header X-Tenant-ID or as parameter'
            ], 400);
        }

        // Find and initialize tenant
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant not found',
                'message' => 'Invalid tenant identifier'
            ], 404);
        }

        if (!$tenant->active) {
            return response()->json([
                'error' => 'Tenant inactive',
                'message' => 'This tenant account is not active'
            ], 403);
        }

        // Initialize tenancy
        $this->tenancy->initialize($tenant);

        return $next($request);
    }
}
