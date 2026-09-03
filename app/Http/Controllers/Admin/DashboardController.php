<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Queue;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $thisWeekStart = now()->startOfWeek();
        $thisWeekEnd   = now()->endOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd   = now()->subWeek()->endOfWeek();

        $today = today()->format('Y-m-d');

        // Single aggregate query instead of 11 separate COUNT() queries on the
        // same appointments table.
        $agg = Appointment::selectRaw(
            'COUNT(*) as total,
             SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as this_week,
             SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as last_week,
             SUM(CASE WHEN status = ? AND created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as cancelled_this_week,
             SUM(CASE WHEN DATE(starts_at) = ? THEN 1 ELSE 0 END) as total_today,
             SUM(CASE WHEN DATE(starts_at) = ? AND status = ? THEN 1 ELSE 0 END) as completed_today,
             SUM(CASE WHEN DATE(starts_at) = ? AND status = ? THEN 1 ELSE 0 END) as confirmed_today,
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_total,
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as confirmed_total,
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_total,
             SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled_total',
            [
                $thisWeekStart,
                $thisWeekEnd,
                $lastWeekStart,
                $lastWeekEnd,
                'cancelled',
                $thisWeekStart,
                $thisWeekEnd,
                $today,
                $today,
                'completed',
                $today,
                'confirmed',
                'pending',
                'confirmed',
                'completed',
                'cancelled',
            ]
        )->first();

        $totalAppointments    = (int) $agg->total;
        $thisWeekAppointments = (int) $agg->this_week;
        $lastWeekAppointments = (int) $agg->last_week;
        $cancelledThisWeek    = (int) $agg->cancelled_this_week;
        $totalToday           = (int) $agg->total_today;
        $completedToday       = (int) $agg->completed_today;
        $confirmedToday       = (int) $agg->confirmed_today;

        $statusDistribution = [
            'pending'   => (int) $agg->pending_total,
            'confirmed' => (int) $agg->confirmed_total,
            'completed' => (int) $agg->completed_total,
            'cancelled' => (int) $agg->cancelled_total,
        ];

        $appointmentsChange = $lastWeekAppointments > 0
            ? round((($thisWeekAppointments - $lastWeekAppointments) / $lastWeekAppointments) * 100)
            : ($thisWeekAppointments > 0 ? 100 : 0);

        $queueCount      = Queue::whereIn('status', ['waiting', 'serving'])->count();
        $totalCustomers  = Customer::count();
        $totalStaff      = Staff::count();
        $newCustomersThisWeek = Customer::whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])->count();

        $attendanceRate   = $totalToday > 0 ? round(($completedToday / $totalToday) * 100) : 0;
        $cancellationRate = $thisWeekAppointments > 0 ? round(($cancelledThisWeek / $thisWeekAppointments) * 100) : 0;

        // Revenue (optional - only if Invoice model exists)
        $thisMonthRevenue = $lastMonthRevenue = $revenueChange = 0;
        if (class_exists(\App\Models\Invoice::class)) {
            $thisMonthRevenue = \App\Models\Invoice::where('status', 'paid')->whereMonth('created_at', now()->month)->sum('amount');
            $lastMonthRevenue = \App\Models\Invoice::where('status', 'paid')->whereMonth('created_at', now()->subMonth()->month)->sum('amount');
            $revenueChange    = $lastMonthRevenue > 0 ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100) : 0;
        }

        $stats = [
            'total_appointments'       => $totalAppointments,
            'appointments_change'      => $appointmentsChange,
            'confirmed'                => $confirmedToday,
            'queue'                   => $queueCount,
            'customers'               => $totalCustomers,
            'new_customers_this_week' => $newCustomersThisWeek,
            'attendance_rate'          => $attendanceRate,
            'cancellation_rate'        => $cancellationRate,
            'total_staff'              => $totalStaff,
            'revenue_this_month'       => $thisMonthRevenue,
            'revenue_change'           => $revenueChange,
        ];

        $todayAppointments = Appointment::with(['customer', 'staff', 'service'])
            ->whereDate('starts_at', today())
            ->orderBy('starts_at')
            ->get();

        $currentQueue = Queue::with(['appointment.customer'])
            ->whereIn('status', ['waiting', 'serving'])
            ->orderByDesc('is_vip')
            ->orderBy('id')
            ->get();

        // Last 7 days chart
        $chartData = ['labels' => [], 'appointments' => []];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $chartData['labels'][]       = $d->format('D');
            $chartData['appointments'][] = Appointment::whereDate('created_at', $d->format('Y-m-d'))->count();
        }

        $topServices = Appointment::selectRaw('service_id, COUNT(*) as total')
            ->whereNotNull('service_id')
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('service')
            ->get()
            ->map(fn ($item) => (object) ['name' => $item->service->name ?? 'N/A', 'total' => $item->total]);

        $staffPerformance = Staff::query()
            ->withCount(['appointments as total_appointments' => fn ($q) => $q->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])])
            ->withCount(['appointments as completed_appointments' => fn ($q) => $q->where('status', 'completed')->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])])
            ->orderByDesc('total_appointments')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'name'      => $s->full_name,
                'avatar'    => $s->avatar ?? null,
                'total'     => $s->total_appointments ?? 0,
                'completed' => $s->completed_appointments ?? 0,
                'rate'      => ($s->total_appointments ?? 0) > 0 ? round(($s->completed_appointments / $s->total_appointments) * 100) : 0,
            ]);

        $recentCustomers = Customer::withCount('appointments')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'name'               => $c->full_name,
                'email'              => $c->email,
                'avatar'             => $c->avatar ?? null,
                'appointments_count' => $c->appointments_count,
                'joined'             => $c->created_at->diffForHumans(),
            ]);

        $recentActivities = Appointment::with(['customer', 'staff'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(fn ($a) => [
                'type'        => $a->status,
                'customer'    => $a->customer?->name ?? 'Unknown',
                'staff'       => $a->staff?->name ?? 'N/A',
                'time'        => $a->updated_at->diffForHumans(),
                'description' => $this->activityDescription($a->status),
            ]);

        $subscriptionInfo = $this->subscriptionInfo();

        return view('admin.dashboard.index', compact(
            'stats',
            'todayAppointments',
            'currentQueue',
            'chartData',
            'topServices',
            'staffPerformance',
            'recentCustomers',
            'recentActivities',
            'subscriptionInfo',
            'statusDistribution'
        ));
    }

    private function activityDescription(string $status): string
    {
        return match ($status) {
            'pending'   => 'New appointment created',
            'confirmed' => 'Appointment confirmed',
            'completed' => 'Appointment completed',
            'cancelled' => 'Appointment cancelled',
            default     => 'Status updated',
        };
    }

    private function subscriptionInfo(): ?array
    {
        try {
            $tenant = tenant();
            if (! $tenant) {
                return null;
            }

            $subscription = DB::connection(config('tenancy.database.central_connection', 'mysql'))->table('tenant_subscriptions')
                ->join('subscription_plans', 'tenant_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
                ->where('tenant_subscriptions.tenant_id', $tenant->id)
                ->whereIn('tenant_subscriptions.status', ['active', 'trial'])
                ->select(
                    'subscription_plans.name as plan_name',
                    'subscription_plans.max_users',
                    'subscription_plans.max_appointments',
                    'tenant_subscriptions.ends_at',
                    'tenant_subscriptions.trial_ends_at',
                    'tenant_subscriptions.status'
                )
                ->orderByRaw("FIELD(tenant_subscriptions.status, 'active', 'trial')")
                ->orderByDesc('tenant_subscriptions.created_at')
                ->first();

            if (!$subscription) {
                return null;
            }

            $currentUsers        = User::count();
            $currentAppointments = Appointment::whereMonth('created_at', now()->month)->count();

            $endsAt = $subscription->status === 'trial'
                ? $subscription->trial_ends_at
                : $subscription->ends_at;

            $daysRemaining = $endsAt ? max(0, (int) now()->diffInDays($endsAt, false)) : 0;

            return [
                'plan_name'      => $subscription->plan_name,
                'status'         => $subscription->status,
                'ends_at'        => $endsAt,
                'days_remaining' => $daysRemaining,
                'limits'         => [
                    'users' => [
                        'max'        => $subscription->max_users == -1 ? 'Unlimited' : $subscription->max_users,
                        'current'    => $currentUsers,
                        'percentage' => $subscription->max_users > 0 ? round(($currentUsers / $subscription->max_users) * 100) : 0,
                    ],
                    'appointments' => [
                        'max'        => $subscription->max_appointments == -1 ? 'Unlimited' : $subscription->max_appointments,
                        'current'    => $currentAppointments,
                        'percentage' => $subscription->max_appointments > 0 ? round(($currentAppointments / $subscription->max_appointments) * 100) : 0,
                    ],
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Error getting subscription info: ' . $e->getMessage());
            return null;
        }
    }
}
