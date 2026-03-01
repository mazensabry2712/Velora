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

        // After status changes, auto-create in-app notifications for the customer
        static::updated(function ($appointment) {
            if (!$appointment->wasChanged('status') || !$appointment->customer_id) {
                return;
            }

            $newStatus = $appointment->status;

            $messages = [
                'confirmed' => [
                    'ar' => 'تم تأكيد موعدك بتاريخ ' . \Carbon\Carbon::parse($appointment->date)->format('Y-m-d') . ' الساعة ' . $appointment->time_slot,
                    'en' => 'Your appointment on ' . \Carbon\Carbon::parse($appointment->date)->format('Y-m-d') . ' at ' . $appointment->time_slot . ' has been confirmed.',
                ],
                'cancelled' => [
                    'ar' => 'تم إلغاء موعدك بتاريخ ' . \Carbon\Carbon::parse($appointment->date)->format('Y-m-d'),
                    'en' => 'Your appointment on ' . \Carbon\Carbon::parse($appointment->date)->format('Y-m-d') . ' has been cancelled.',
                ],
                'completed' => [
                    'ar' => 'تمت خدمتك بنجاح، شكراً لزيارتك!',
                    'en' => 'Your appointment has been completed. Thank you for your visit!',
                ],
            ];

            if (!isset($messages[$newStatus])) {
                return;
            }

            $locale  = app()->getLocale();
            $message = $messages[$newStatus][$locale] ?? $messages[$newStatus]['en'];

            try {
                \App\Models\Notification::create([
                    'user_id'        => $appointment->customer_id,
                    'appointment_id' => $appointment->id,
                    'type'           => 'status_change',
                    'message'        => $message,
                    'is_read'        => false,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Auto-notification failed for appointment ' . $appointment->id . ': ' . $e->getMessage());
            }

            // Auto-generate invoice when appointment is completed and service has a price
            if ($newStatus === 'completed') {
                try {
                    $service = $appointment->service;
                    $price   = $service?->price ?? 0;

                    if ($price > 0) {
                        \App\Models\Invoice::create([
                            'customer_id' => $appointment->customer_id,
                            'amount'      => $price,
                            'status'      => 'pending',
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Auto-invoice failed for appointment ' . $appointment->id . ': ' . $e->getMessage());
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
