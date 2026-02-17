<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{

    protected $fillable = [
        'customer_id',
        'staff_id',
        'service_id',
        'date',
        'time_slot',
        'status',
        'service_type',
        'notes',
        'rating',
        'rating_comment',
        'rated_at',
    ];

    protected $casts = [
        'date' => 'date',
        'rated_at' => 'datetime',
    ];

    // Model Events
    protected static function booted()
    {
        // When appointment is deleted, delete associated queue entry
        static::deleting(function ($appointment) {
            $appointment->queue()?->delete();
        });

        // When appointment status changes, sync with queue
        static::updating(function ($appointment) {
            if ($appointment->isDirty('status') && $appointment->queue) {
                $newStatus = $appointment->status;

                // Sync queue status based on appointment status
                if ($newStatus === 'cancelled') {
                    $appointment->queue->update(['status' => 'cancelled']);
                } elseif ($newStatus === 'completed') {
                    $appointment->queue->update(['status' => 'completed']);
                } elseif ($newStatus === 'confirmed' && $appointment->queue->status === 'cancelled') {
                    // If re-confirming a cancelled appointment, reactivate queue
                    $appointment->queue->update(['status' => 'waiting']);
                }
            }
        });
    }

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function queue()
    {
        return $this->hasOne(Queue::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', today())
            ->whereNotIn('status', ['cancelled', 'completed']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeInQueue($query)
    {
        return $query->whereHas('queue', function ($q) {
            $q->whereIn('status', ['waiting', 'serving']);
        });
    }

    // Helper Methods
    public function canBeAddedToQueue(): bool
    {
        // Can't add if already in queue
        if ($this->queue) {
            return false;
        }

        // Can't add cancelled or completed appointments
        if (in_array($this->status, ['cancelled', 'completed'])) {
            return false;
        }

        return true;
    }

    public function isOverdue(): bool
    {
        return $this->date < today() && $this->status !== 'completed';
    }

    public function isSoon(): bool
    {
        $now = now();
        $appointmentDate = \Carbon\Carbon::parse($this->date);

        return $appointmentDate->isToday() &&
               $appointmentDate->diffInHours($now) <= 2 &&
               $appointmentDate > $now;
    }

    /**
     * Get service name (from service relation or service_type field)
     */
    public function getServiceNameAttribute()
    {
        if ($this->service) {
            return app()->getLocale() === 'ar' && $this->service->name_ar
                ? $this->service->name_ar
                : $this->service->name;
        }
        return $this->service_type;
    }
}
