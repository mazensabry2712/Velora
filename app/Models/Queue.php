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

                // Sync appointment based on queue status
                if ($newStatus === 'completed') {
                    // When queue is completed, complete appointment
                    $queue->appointment->update(['status' => 'completed']);
                } elseif (in_array($newStatus, ['cancelled', 'skipped'])) {
                    // When queue is cancelled/skipped, cancel appointment
                    $queue->appointment->update(['status' => 'cancelled']);
                } elseif ($newStatus === 'serving') {
                    // When customer is being served, confirm appointment
                    if ($queue->appointment->status !== 'confirmed') {
                        $queue->appointment->update(['status' => 'confirmed']);
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
            ->orderByRaw('CAST(queue_number AS UNSIGNED) DESC')
            ->first();

        $nextNumber = $lastQueue ? ((int) $lastQueue->queue_number + 1) : 1;

        return (string) $nextNumber;
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
