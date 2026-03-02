<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;

class DashboardController extends Controller
{
    /**
     * Get Super Admin dashboard statistics.
     */
    public function index()
    {
        $allTenants = Tenant::with('domains')->get();

        $stats = [
            'total_tenants' => $allTenants->count(),
            'active_tenants' => $allTenants->filter(fn($t) => $t->active)->count(),
            'inactive_tenants' => $allTenants->filter(fn($t) => !$t->active)->count(),
            'tenants_this_month' => Tenant::whereMonth('created_at', now()->month)->count(),
            'recent_tenants' => Tenant::with('domains')->latest()->take(10)->get()->map(function ($tenant) {
                $domain = $tenant->domains->first();
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name ?? 'N/A',
                    'subdomain' => $domain ? str_replace('.'.config('app.domain', 'booking-saas.test'), '', $domain->domain) : $tenant->id,
                    'is_active' => $tenant->active ?? true,
                    'created_at' => $tenant->created_at->toISOString(),
                ];
            }),
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
        $tenants = Tenant::with('domains')->latest()->get()->map(function ($tenant) {
            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domains->first()?->domain ?? 'N/A',
                'active' => $tenant->active,
                'created_at' => $tenant->created_at->toISOString(),
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
        $allTenants = Tenant::all();

        $stats = [
            'total_tenants' => $allTenants->count(),
            'active_tenants' => $allTenants->filter(fn($t) => $t->active)->count(),
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

    /**
     * Get subscription statistics
     */
    public function subscriptionStats()
    {
        $plans = \App\Models\SubscriptionPlan::withCount([
            'subscriptions as active_count' => function ($query) {
                $query->where('status', 'active');
            },
            'subscriptions as trial_count' => function ($query) {
                $query->where('status', 'trial');
            }
        ])->get();

        $totalRevenue = \App\Models\TenantSubscription::where('status', 'active')
            ->sum('amount_paid');

        $monthlyRevenue = \App\Models\TenantSubscription::where('status', 'active')
            ->whereMonth('created_at', now()->month)
            ->sum('amount_paid');

        return response()->json([
            'success' => true,
            'data' => [
                'plans' => $plans,
                'total_revenue' => $totalRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'total_subscriptions' => \App\Models\TenantSubscription::count(),
                'active_subscriptions' => \App\Models\TenantSubscription::where('status', 'active')->count(),
                'trial_subscriptions' => \App\Models\TenantSubscription::where('status', 'trial')->count(),
            ]
        ]);
    }

    /**
     * Get activity summary
     */
    public function activitySummary()
    {
        try {
            $recentActivitiesRaw = \App\Models\ActivityLog::select('id', 'action', 'description', 'created_at', 'user_id')
                ->with(['user:id,name'])
                ->latest()
                ->take(10)
                ->get();

            $recent = $recentActivitiesRaw->map(function ($a) {
                $action = $a->action ?? 'edit';
                $type = in_array($action, ['created', 'login', 'register', 'add']) ? 'add'
                      : ($action === 'deleted' ? 'delete' : 'edit');
                return [
                    'id'          => $a->id,
                    'action'      => $action,
                    'type'        => $type,
                    'description' => $a->description ?? '',
                    'user'        => $a->user?->name ?? 'System',
                    'created_at'  => $a->created_at->toISOString(),
                ];
            })->values();

            $todayCount = \App\Models\ActivityLog::whereDate('created_at', today())->count();
            $weekCount  = \App\Models\ActivityLog::whereBetween('created_at', [
                now()->startOfWeek(), now()->endOfWeek()
            ])->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'recent'      => $recent,
                    'today_count' => $todayCount,
                    'week_count'  => $weekCount,
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('activitySummary error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch activity summary',
                'data'    => ['recent' => [], 'today_count' => 0, 'week_count' => 0],
            ], 500);
        }
    }

    /**
     * Get growth metrics
     */
    public function growthMetrics()
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'tenants' => Tenant::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'revenue' => \App\Models\TenantSubscription::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('status', 'active')
                    ->sum('amount_paid'),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $months
        ]);
    }
}
