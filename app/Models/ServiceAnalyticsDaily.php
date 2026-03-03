<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceAnalyticsDaily extends Model
{
    protected $table = 'service_analytics_daily';

    protected $fillable = [
        'service_id',
        'date',
        'bookings_count',
        'completed',
        'cancelled',
        'revenue',
        'avg_booking_value',
    ];

    protected $casts = [
        'date'             => 'date',
        'revenue'          => 'decimal:2',
        'avg_booking_value'=> 'decimal:2',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
