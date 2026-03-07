<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Queue;
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

        $totalAppointments     = Appointment::count();
        $lastWeekAppointments  = Appointment::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();
        $thisWeekAppointments  = Appointment::whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])->count();
        $cancelledThisWeek     = Appointment::where('status', 'cancelled')->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])->count();

        $appointmentsChange = $lastWeekAppointments > 0
            ? round((($thisWeekAppointments - $lastWeekAppointments) / $lastWeekAppointments) * 100)
            : ($thisWeekAppointments > 0 ? 100 : 0);

        $today          = today()->format('Y-m-d');
        $completedToday = Appointment::where('status', 'completed')->where('date', $today)->count();
        $totalToday     = Appointment::where('date', $today)->count();
        $confirmedToday = Appointment::where('status', 'confirmed')->where('date', $today)->count();

        $queueCount      = Queue::whereIn('status', ['waiting', 'serving'])->count();
        $totalCustomers  = User::whereHas('role', fn ($q) => $q->where('name', 'Customer'))->count();
        $totalStaff      = User::whereHas('role', fn ($q) => $q->where('name', 'Staff'))->count();
        $newCustomersThisWeek = User::whereHas('role', fn ($q) => $q->where('name', 'Customer'))
            ->whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])->count();

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
            'total_appointments'    => $totalAppointments,
            'appointments_change'   => $appointmentsChange,
            'confirmed'             => $confirmedToday,
            'queue'                 => $queueCount,
            'customers'             => $totalCustomers,
            'new_customers_this_week' => $newCustomersThisWeek,
            'attendance_rate'       => $attendanceRate,
            'cancellation_rate'     => $cancellationRate,
            'total_staff'           => $totalStaff,
            'revenue_this_month'    => $thisMonthRevenue,
            'revenue_change'        => $revenueChange,
        ];

        $todayAppointments = Appointment::with(['customer', 'staff', 'service'])
            ->whereDate('date', today())
            ->orderBy('time_slot')
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

        $staffPerformance = User::whereHas('role', fn ($q) => $q->where('name', 'Staff'))
            ->withCount(['staffAppointments as total_appointments' => fn ($q) => $q->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])])
            ->withCount(['staffAppointments as completed_appointments' => fn ($q) => $q->where('status', 'completed')->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])])
            ->orderByDesc('total_appointments')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'name'      => $s->name,
                'avatar'    => $s->avatar_url ?? null,
                'total'     => $s->total_appointments  ?? 0,
                'completed' => $s->completed_appointments ?? 0,
                'rate'      => ($s->total_appointments ?? 0) > 0 ? round(($s->completed_appointments / $s->total_appointments) * 100) : 0,
            ]);

        $recentCustomers = User::whereHas('role', fn ($q) => $q->where('name', 'Customer'))
            ->withCount('appointments')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'name'               => $c->name,
                'email'              => $c->email,
                'avatar'             => $c->avatar_url ?? null,
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

        $statusDistribution = [
            'pending'   => Appointment::where('status', 'pending')->count(),
            'confirmed' => Appointment::where('status', 'confirmed')->count(),
            'completed' => Appointment::where('status', 'completed')->count(),
            'cancelled' => Appointment::where('status', 'cancelled')->count(),
        ];

        $subscriptionInfo = $this->subscriptionInfo();

        return view('admin.dashboard.index', compact(
            'stats', 'todayAppointments', 'currentQueue',
            'chartData', 'topServices', 'staffPerformance',
            'recentCustomers', 'recentActivities',
            'subscriptionInfo', 'statusDistribution'
        ));
    }

    // ── Private helpers ──────────────────────────────────────────────────

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
            if (!$tenant) {
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
                'plan_name'     => $subscription->plan_name,
                'status'        => $subscription->status,
                'ends_at'       => $endsAt,
                'days_remaining' => $daysRemaining,
                'limits' => [
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
