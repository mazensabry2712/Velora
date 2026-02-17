<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get Super Admin dashboard statistics.
     */
    public function index()
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('active', true)->count(),
            'inactive_tenants' => Tenant::where('active', false)->count(),
            'recent_tenants' => Tenant::latest()->take(5)->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get all tenants overview.
     */
    public function tenantsOverview()
    {
        $tenants = Tenant::withCount(['users'])
            ->with(['domains', 'settings'])
            ->latest()
            ->get()
            ->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                    'active' => $tenant->active,
                    'users_count' => $tenant->users_count ?? 0,
                    'language' => $tenant->settings->language ?? 'en',
                    'created_at' => $tenant->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $tenants
        ]);
    }

    /**
     * Get system-wide statistics.
     */
    public function systemStats()
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('active', true)->count(),
            'tenants_this_month' => Tenant::whereMonth('created_at', now()->month)->count(),
            'tenants_today' => Tenant::whereDate('created_at', today())->count(),
        ];

        // Get chart data for last 30 days
        $chartData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $chartData[] = [
                'date' => $date,
                'tenants' => Tenant::whereDate('created_at', $date)->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'chart' => $chartData,
            ]
        ]);
    }
}
