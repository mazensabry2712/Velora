<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitingList extends Model
{
    protected $table = 'waiting_list';

    protected $fillable = [
        'customer_id', 'service_id', 'staff_id', 'preferred_date',
        'preferred_days', 'preferred_time_from', 'preferred_time_to',
        'status', 'notified_at', 'notification_count', 'expires_at',
        'converted_appointment_id', 'converted_at', 'metadata',
    ];

    protected $casts = [
        'preferred_date'  => 'date',
        'preferred_days'  => 'array',
        'metadata'        => 'array',
        'notified_at'     => 'datetime',
        'expires_at'      => 'datetime',
        'converted_at'    => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function convertedAppointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'converted_appointment_id');
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting')
                     ->where(function ($q) {
                         $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                     });
    }
}
