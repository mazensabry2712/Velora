<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{

    protected $fillable = [
        'appointment_id',
        'queue_number',
        'queue_date',
        'status',
        'is_vip',
        'counter_number',
        'notes',
    ];

    protected $casts = [
        'is_vip' => 'boolean',
        'queue_date' => 'date',
    ];

    // Model Events
    protected static function booted()
    {
        // When queue status changes, sync with appointment
        static::updating(function ($queue) {
            if ($queue->isDirty('status') && $queue->appointment) {
                $newStatus = $queue->status;

                // Use withoutEvents to prevent circular update loop between Queue and Appointment observers
                $appointment = $queue->appointment;
                if ($newStatus === 'completed') {
                    \Illuminate\Database\Eloquent\Model::withoutEvents(fn () =>
                        $appointment->update(['status' => 'completed'])
                    );
                } elseif (in_array($newStatus, ['cancelled', 'skipped'])) {
                    \Illuminate\Database\Eloquent\Model::withoutEvents(fn () =>
                        $appointment->update(['status' => 'cancelled'])
                    );
                } elseif ($newStatus === 'serving') {
                    if ($appointment->status !== 'confirmed') {
                        \Illuminate\Database\Eloquent\Model::withoutEvents(fn () =>
                            $appointment->update(['status' => 'confirmed'])
                        );
                    }
                }
            }
        });
    }

    /**
     * Generate next queue number for today
     * Format: Simple sequential number (1, 2, 3...)
     */
    public static function generateQueueNumber(): string
    {
        // Get the last queue number for today
        $lastQueue = self::whereDate('created_at', now()->toDateString())
            ->orderByRaw("CAST(SUBSTRING(queue_number, 2) AS UNSIGNED) DESC")
            ->first();

        if ($lastQueue && preg_match('/^[A-Z](\d+)$/', $lastQueue->queue_number, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return 'A' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    // Get customer through appointment
    public function customer()
    {
        return $this->hasOneThrough(
            User::class,
            Appointment::class,
            'id', // Foreign key on appointments table
            'id', // Foreign key on users table
            'appointment_id', // Local key on queues table
            'customer_id' // Local key on appointments table
        );
    }
}
