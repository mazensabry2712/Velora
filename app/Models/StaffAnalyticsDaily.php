<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAnalyticsDaily extends Model
{
    protected $table = 'staff_analytics_daily';

    protected $fillable = [
        'staff_id',
        'date',
        'bookings_count',
        'completed',
        'cancelled',
        'no_shows',
        'revenue',
        'commission_earned',
        'utilization_pct',
        'unique_customers',
        'calculated_at',
    ];

    protected $casts = [
        'date'             => 'date',
        'calculated_at'    => 'datetime',
        'revenue'          => 'decimal:2',
        'commission_earned'=> 'decimal:2',
        'utilization_pct'  => 'decimal:2',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
