<?php

namespace App\Models;

use App\Observers\QueueObserver;
use Carbon\Carbon;
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

    protected static function booted(): void
    {
        static::observe(QueueObserver::class);

        static::updating(function ($queue) {
            if ($queue->isDirty('status') && $queue->appointment) {
                $newStatus = $queue->status;
                $appointment = $queue->appointment;

                if ($newStatus === 'completed') {
                    Model::withoutEvents(fn () =>
                        $appointment->update(['status' => 'completed'])
                    );
                } elseif (in_array($newStatus, ['cancelled', 'skipped'], true)) {
                    Model::withoutEvents(fn () =>
                        $appointment->update(['status' => 'cancelled'])
                    );
                } elseif ($newStatus === 'serving') {
                    if ($appointment->status !== 'confirmed') {
                        Model::withoutEvents(fn () =>
                            $appointment->update(['status' => 'confirmed'])
                        );
                    }
                }
            }
        });
    }

    public static function generateQueueNumber(?Carbon $date = null): string
    {
        $queueDate = ($date ?: now())->toDateString();

        $lastQueue = self::where(function ($query) use ($queueDate) {
                $query->whereDate('queue_date', $queueDate)
                    ->orWhere(function ($fallback) use ($queueDate) {
                        $fallback->whereNull('queue_date')->whereDate('created_at', $queueDate);
                    });
            })
            ->orderByRaw("CAST(SUBSTRING(queue_number, 2) AS UNSIGNED) DESC")
            ->first();

        if ($lastQueue && preg_match('/^[A-Z](\d+)$/', $lastQueue->queue_number, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return 'A' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function customer()
    {
        return $this->hasOneThrough(
            User::class,
            Appointment::class,
            'id',
            'id',
            'appointment_id',
            'customer_id'
        );
    }
}
