<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Daily business-level KPIs (pre-aggregated).
 * Populated nightly by App\Console\Commands\AggregateAnalytics.
 */
class AnalyticsDaily extends Model
{
    protected $table = 'analytics_daily';

    protected $fillable = [
        'date',
        'total_bookings',
        'confirmed',
        'completed',
        'cancelled',
        'no_shows',
        'pending',
        'rescheduled',
        'new_customers',
        'returning_customers',
        'unique_customers',
        'gross_revenue',
        'net_revenue',
        'deposit_revenue',
        'avg_booking_value',
        'utilization_pct',
        'total_slots_available',
        'total_slots_booked',
        'online_bookings',
        'walkin_bookings',
        'calculated_at',
    ];

    protected $casts = [
        'date'              => 'date',
        'calculated_at'     => 'datetime',
        'gross_revenue'     => 'decimal:2',
        'net_revenue'       => 'decimal:2',
        'deposit_revenue'   => 'decimal:2',
        'avg_booking_value' => 'decimal:2',
        'utilization_pct'   => 'decimal:2',
    ];
}
