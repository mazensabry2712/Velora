<?php

namespace App\Services;

use App\Domain\Reporting\Contracts\ReportReader;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Queue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService implements ReportReader
{
    /**
     * Periods accepted by the dashboard filter and the Excel export,
     * kept in one place so both stay in sync.
     */
    public const PERIODS = ['today', 'week', 'month', 'year', 'all', 'custom'];

    public function dashboard(string $period = 'month', ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->getDashboardData($period, $startDate, $endDate);
    }

    /**
     * Resolve a period keyword (+ optional explicit range) into a
     * concrete [start, end] Carbon range. Returns [null, null] for
     * "all time".
     */
    public function resolveRange(string $period, ?string $startDate, ?string $endDate): array
    {
        if ($startDate && $endDate) {
            return [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()];
        }

        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week'  => [now()->startOfWeek(), now()->endOfWeek()],
            'year'  => [now()->startOfYear(), now()->endOfYear()],
            'all'   => [null, null],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    /**
     * Build every dataset the reports dashboard needs for the given
     * period. Queue stats reflect the live queue and are intentionally
     * not scoped to the date range; other datasets are range-scoped.
     */
    public function getDashboardData(string $period = 'month', ?string $startDate = null, ?string $endDate = null): array
    {
        if (!in_array($period, self::PERIODS, true)) {
            $period = 'month';
        }

        [$start, $end] = $this->resolveRange($period, $startDate, $endDate);

        return [
            'period'               => $period,
            'startDate'            => $start?->toDateString(),
            'endDate'              => $end?->toDateString(),
            'stats'                => $this->getStats($start, $end),
            'appointmentsByStatus' => $this->getAppointmentsByStatus($start, $end),
            'queueStats'           => $this->getQueueStats(),
            'staffPerformance'     => $this->getStaffPerformance($start, $end),
            'serviceTypes'         => $this->getServiceTypes($start, $end),
        ];
    }

    protected function scopeToRange($query, ?Carbon $start, ?Carbon $end)
    {
        if ($start && $end) {
            $query->whereBetween('date', [$start, $end]);
        }

        return $query;
    }

    public function getStats(?Carbon $start, ?Carbon $end): array
    {
        return [
            'total_appointments'      => $this->scopeToRange(Appointment::query(), $start, $end)->count(),
            'confirmed_appointments'  => $this->scopeToRange(Appointment::where('status', 'confirmed'), $start, $end)->count(),
            'pending_appointments'    => $this->scopeToRange(Appointment::where('status', 'pending'), $start, $end)->count(),
            'total_customers'         => Customer::count(),
        ];
    }

    public function getAppointmentsByStatus(?Carbon $start, ?Carbon $end)
    {
        return $this->scopeToRange(
            Appointment::select('status', DB::raw('count(*) as count')),
            $start,
            $end
        )->groupBy('status')->get();
    }

    public function getQueueStats(): array
    {
        return [
            'waiting'   => Queue::where('status', 'waiting')->count(),
            'serving'   => Queue::where('status', 'serving')->count(),
            'completed' => Queue::where('status', 'completed')->count(),
            'priority'  => Queue::where('is_vip', true)->whereIn('status', ['waiting', 'serving'])->count(),
        ];
    }

    public function getStaffPerformance(?Carbon $start, ?Carbon $end)
    {
        $query = User::role(['Admin Tenant', 'Staff'])
            ->withCount(['staffAppointments' => function ($q) use ($start, $end) {
                $q->where('status', 'confirmed');
                $this->scopeToRange($q, $start, $end);
            }]);

        $query->whereHas('staffAppointments', function ($q) use ($start, $end) {
            $q->where('status', 'confirmed');
            $this->scopeToRange($q, $start, $end);
        });

        return $query
            ->orderByDesc('staff_appointments_count')
            ->get();
    }

    public function getServiceTypes(?Carbon $start, ?Carbon $end)
    {
        return $this->scopeToRange(
            Appointment::whereNotNull('service_type')
                ->select('service_type', DB::raw('count(*) as count')),
            $start,
            $end
        )->groupBy('service_type')->orderByDesc('count')->get();
    }
}
