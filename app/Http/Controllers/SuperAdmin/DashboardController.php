<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\SubscriptionPlan;
use App\Models\UpgradeRequest;

class DashboardController extends Controller
{
    /**
     * Get Super Admin dashboard statistics.
     */
    public function index()
    {
        $stats = [
            'total_tenants'             => Tenant::count(),
            'active_tenants'            => TenantSubscription::where('status', 'active')->distinct('tenant_id')->count(),
            'trial_tenants'             => TenantSubscription::where('status', 'trial')->distinct('tenant_id')->count(),
            'inactive_tenants'          => Tenant::whereDoesntHave('subscriptions', function ($q) {
                                               $q->whereIn('status', ['active', 'trial']);
                                           })->count(),
            'tenants_this_month'        => Tenant::whereYear('created_at', now()->year)
                                               ->whereMonth('created_at', now()->month)
                                               ->count(),
            'pending_upgrade_requests'  => UpgradeRequest::where('status', 'pending')->count(),
            'recent_tenants'            => Tenant::with('subscriptions')
                                               ->orderBy('created_at', 'desc')
                                               ->limit(10)
                                               ->get()
                                               ->map(function ($tenant) {
                                                   return [
                                                       'id'         => $tenant->id,
                                                       'name'       => $tenant->name ?? 'N/A',
                                                       'subdomain'  => $tenant->id,
                                                       'is_active'  => $tenant->subscriptions->whereIn('status', ['active', 'trial'])->count() > 0,
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

    /**
     * Revenue KPIs for the trial-to-paid funnel.
     *
     * Returns:
     *   - mrr                  Monthly Recurring Revenue (sum of active subs)
     *   - trial_signups_30d    New trial tenants in the last 30 days
     *   - activated_rate       % of trial tenants that completed onboarding
     *   - aha_rate             % of trial tenants that reached the Aha Moment
     *   - trial_to_paid_rate   % of trial subs that converted to paid
     *   - churn_rate           Cancelled subs / total paid subs (last 30 days)
     *   - arpu                 Average Revenue per Paid User
     */
    public function revenueMetrics()
    {
        $now = now();

        // ── MRR ──────────────────────────────────────────────────────────
        $mrr = TenantSubscription::where('status', 'active')->sum('amount_paid');

        // ── Trial funnel ─────────────────────────────────────────────────
        $trialTotal = TenantSubscription::where('status', 'trial')->count();
        $trialLast30 = TenantSubscription::where('status', 'trial')
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->count();

        $activated     = TenantSubscription::whereNotNull('activated_at')->count();
        $ahaReached    = TenantSubscription::where('aha_reached', true)->count();
        $converted     = TenantSubscription::whereNotNull('converted_at')->count();

        $totalTrialEver = TenantSubscription::whereIn('status', ['trial', 'active', 'cancelled', 'expired'])->count();

        $activatedRate   = $totalTrialEver > 0 ? round($activated   / $totalTrialEver * 100, 1) : 0;
        $ahaRate         = $totalTrialEver > 0 ? round($ahaReached  / $totalTrialEver * 100, 1) : 0;
        $trialToPaidRate = $totalTrialEver > 0 ? round($converted   / $totalTrialEver * 100, 1) : 0;

        // ── Churn (cancelled in last 30 days / total paid ever) ───────────
        $cancelledLast30 = TenantSubscription::where('status', 'cancelled')
            ->where('updated_at', '>=', $now->copy()->subDays(30))
            ->count();
        $totalPaidEver = TenantSubscription::where('status', 'active')
            ->orWhere('status', 'cancelled')
            ->count();
        $churnRate = $totalPaidEver > 0 ? round($cancelledLast30 / $totalPaidEver * 100, 1) : 0;

        // ── ARPU ──────────────────────────────────────────────────────────
        $paidCount = TenantSubscription::where('status', 'active')->count();
        $arpu      = $paidCount > 0 ? round($mrr / $paidCount, 2) : 0;

        // ── Nudge funnel breakdown ────────────────────────────────────────
        $nudgeStats = [];
        foreach ([1, 3, 7, 12] as $day) {
            $nudgeStats["day{$day}_sent"] = TenantSubscription::whereJsonContains('nudges_sent', $day)->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'mrr'                => (float) $mrr,
                'arpu'               => $arpu,
                'trial_signups_30d'  => $trialLast30,
                'trial_total_active' => $trialTotal,
                'activated_count'    => $activated,
                'activated_rate'     => $activatedRate,
                'aha_reached_count'  => $ahaReached,
                'aha_rate'           => $ahaRate,
                'converted_count'    => $converted,
                'trial_to_paid_rate' => $trialToPaidRate,
                'churn_rate_30d'     => $churnRate,
                'nudge_stats'        => $nudgeStats,
                'generated_at'       => $now->toISOString(),
            ],
        ]);
    }

    public function exportKpis()
    {
        $rows = TenantSubscription::orderByDesc('created_at')->get();

        $csv  = implode(',', [
            'tenant_id', 'status', 'plan', 'amount_paid',
            'trial_ends_at', 'activated_at', 'converted_at',
            'aha_reached', 'aha_reached_at', 'trial_extended', 'trial_extended_at',
            'nudges_sent', 'founder_alerted', 'created_at',
        ]) . "\n";

        foreach ($rows as $r) {
            $nudges = is_array($r->nudges_sent) ? implode('|', $r->nudges_sent) : '';
            $csv .= implode(',', [
                $r->tenant_id,
                $r->status,
                $r->plan ?? '',
                $r->amount_paid ?? '',
                $r->trial_ends_at,
                $r->activated_at,
                $r->converted_at,
                $r->aha_reached ? '1' : '0',
                $r->aha_reached_at,
                $r->trial_extended ? '1' : '0',
                $r->trial_extended_at,
                '"' . str_replace('"', '""', $nudges) . '"',
                $r->founder_alerted ? '1' : '0',
                $r->created_at,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="velora-kpis-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }
}
