<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AggregateAnalytics — fills analytics_daily and service_analytics_daily tables.
 *
 * Runs daily at midnight via schedule for the previous day.
 * Can also be run manually: php artisan analytics:aggregate --date=2026-03-01
 *
 * Aggregated tables allow fast dashboard queries without scanning appointments.
 */
class AggregateAnalytics extends Command
{
    protected $signature   = 'analytics:aggregate {--date= : Date to aggregate (Y-m-d, default: yesterday)} {--tenant= : Run for specific tenant}';
    protected $description = 'Aggregate daily analytics from appointments into analytics tables';

    public function handle(): int
    {
        $dateStr  = $this->option('date') ?? Carbon::yesterday()->toDateString();
        $tenantId = $this->option('tenant');

        try {
            $date = Carbon::parse($dateStr)->startOfDay();
        } catch (\Throwable) {
            $this->error("Invalid date: {$dateStr}");
            return self::FAILURE;
        }

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);
                $this->aggregateTenant($date);
                tenancy()->end();
                $this->line("  ✓ Tenant [{$tenant->id}] aggregated for {$dateStr}");
            } catch (\Throwable $e) {
                Log::error("AggregateAnalytics tenant [{$tenant->id}]: " . $e->getMessage());
                $this->warn("  ✗ Tenant [{$tenant->id}] failed: " . $e->getMessage());
                tenancy()->end();
            }
        }

        $this->info("Analytics aggregation complete for {$dateStr}.");
        return self::SUCCESS;
    }

    private function aggregateTenant(Carbon $date): void
    {
        $dateStr = $date->toDateString();

        // The appointment source of truth is the canonical UTC interval plus
        // the canonical Customer identity. Legacy date/time/customer_id fields
        // are intentionally not used by analytics.
        $appointments = Appointment::whereDate('starts_at', $dateStr)
            ->with('service')
            ->get();

        $total        = $appointments->count();
        $confirmed    = $appointments->where('status', 'confirmed')->count();
        $completed    = $appointments->where('status', 'completed')->count();
        $cancelled    = $appointments->where('status', 'cancelled')->count();
        $noShows      = $appointments->where('status', 'no_show')->count();
        $pending      = $appointments->where('status', 'pending')->count();

        // Revenue: sum service price for completed appointments
        $revenue = $appointments->where('status', 'completed')
            ->sum(fn ($a) => $a->service?->price ?? 0);

        // Customer metrics
        $customerIds      = $appointments->pluck('customer_id_new')->filter()->unique();
        $uniqueCustomers  = $customerIds->count();

        // New vs returning: customer made a first appointment today
        $newCustomers = 0;
        $returningCustomers = 0;
        foreach ($customerIds as $customerId) {
            $firstAppt = Appointment::where('customer_id_new', $customerId)
                ->oldest('starts_at')
                ->value('starts_at');
            if ($firstAppt && Carbon::parse($firstAppt)->isSameDay($date)) {
                $newCustomers++;
            } else {
                $returningCustomers++;
            }
        }

        DB::table('analytics_daily')->updateOrInsert(
            ['date' => $dateStr],
            [
                'total_bookings'      => $total,
                'confirmed'           => $confirmed,
                'completed'           => $completed,
                'cancelled'           => $cancelled,
                'no_shows'            => $noShows,
                'pending'             => $pending,
                'rescheduled'         => 0,
                'new_customers'       => $newCustomers,
                'returning_customers' => $returningCustomers,
                'unique_customers'    => $uniqueCustomers,
                'gross_revenue'       => $revenue,
                'net_revenue'         => $revenue,
                'deposit_revenue'     => 0,
                'avg_booking_value'   => $total > 0 ? round($revenue / $total, 2) : 0,
                'utilization_pct'     => 0,
                'total_slots_available' => 0,
                'total_slots_booked'  => $total,
                'online_bookings'     => 0,
                'walkin_bookings'     => 0,
                'calculated_at'       => now()->toDateTimeString(),
                'updated_at'          => now()->toDateTimeString(),
                'created_at'          => now()->toDateTimeString(),
            ]
        );

        // ── 2. Per-service daily metrics ──────────────────────────────────────
        $serviceGroups = $appointments->whereNotNull('service_id')->groupBy('service_id');

        foreach ($serviceGroups as $serviceId => $serviceAppts) {
            $sTotal     = $serviceAppts->count();
            $sCompleted = $serviceAppts->where('status', 'completed')->count();
            $sCancelled = $serviceAppts->where('status', 'cancelled')->count();
            $sRevenue   = $serviceAppts->where('status', 'completed')
                ->sum(fn ($a) => $a->service?->price ?? 0);

            if (! Service::find($serviceId)) {
                continue;
            }

            DB::table('service_analytics_daily')->updateOrInsert(
                ['service_id' => $serviceId, 'date' => $dateStr],
                [
                    'bookings_count'    => $sTotal,
                    'completed'         => $sCompleted,
                    'cancelled'         => $sCancelled,
                    'revenue'           => $sRevenue,
                    'avg_booking_value' => $sTotal > 0 ? round($sRevenue / $sTotal, 2) : 0,
                    'updated_at'        => now()->toDateTimeString(),
                    'created_at'        => now()->toDateTimeString(),
                ]
            );
        }

        // ── 3. Booking heatmap ────────────────────────────────────────────────
        $weekStart = $date->copy()->startOfWeek()->toDateString();

        $heatRows = $appointments->groupBy(function ($a) {
            $startsAt = Carbon::parse($a->starts_at);
            return $startsAt->dayOfWeek . ':' . $startsAt->hour;
        });

        foreach ($heatRows as $key => $group) {
            [$dow, $hour] = explode(':', $key);
            DB::table('booking_heatmap')->updateOrInsert(
                ['week_start' => $weekStart, 'day_of_week' => $dow, 'hour_of_day' => $hour],
                [
                    'bookings_count' => $group->count(),
                    'revenue_cents'  => (int) ($group->where('status', 'completed')->sum(fn ($a) => ($a->service?->price ?? 0) * 100)),
                    'updated_at'     => now()->toDateTimeString(),
                    'created_at'     => now()->toDateTimeString(),
                ]
            );
        }
    }
}
