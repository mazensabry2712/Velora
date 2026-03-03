<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingHeatmap extends Model
{
    protected $table = 'booking_heatmap';

    protected $fillable = [
        'week_start',
        'day_of_week',
        'hour_of_day',
        'bookings_count',
        'revenue_cents',
    ];

    protected $casts = [
        'week_start' => 'date',
    ];
}
