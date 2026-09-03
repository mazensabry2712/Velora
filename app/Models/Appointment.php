<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW   = 'no_show';

    public const VALID_TRANSITIONS = [
        self::STATUS_PENDING   => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED => [self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_NO_SHOW],
        self::STATUS_COMPLETED => [],
        self::STATUS_CANCELLED => [],
        self::STATUS_NO_SHOW   => [],
    ];

    protected $fillable = [
        'date', 'time_slot', 'service_type',
        'rating', 'rating_comment', 'rated_at',
        'ulid', 'public_reference', 'service_id', 'status', 'notes',
        'customer_id_new', 'staff_id_new', 'resource_id', 'group_id', 'recurring_id',
        'starts_at', 'ends_at', 'ends_at_with_buffer', 'timezone',
        'price', 'deposit_paid', 'discount_amount', 'discount_reason', 'attendees',
        'source', 'internal_notes', 'cancelled_by', 'cancel_reason',
        'confirmed_at', 'completed_at', 'cancelled_at', 'no_show_at',
        'reminder_sent_at', 'metadata', 'deposit_amount',
    ];

    protected $casts = [
        'date'                => 'date',
        'rated_at'            => 'datetime',
        'starts_at'           => 'datetime',
        'ends_at'             => 'datetime',
        'ends_at_with_buffer' => 'datetime',
        'confirmed_at'        => 'datetime',
        'completed_at'        => 'datetime',
        'cancelled_at'        => 'datetime',
        'no_show_at'          => 'datetime',
        'reminder_sent_at'    => 'datetime',
        'price'               => 'decimal:2',
        'deposit_paid'        => 'decimal:2',
        'deposit_amount'      => 'decimal:2',
        'discount_amount'     => 'decimal:2',
        'metadata'            => 'array',
        'attendees'           => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }

            if (empty($model->public_reference)) {
                do {
                    $reference = 'VL-' . Str::upper(Str::random(8));
                } while (self::withTrashed()->where('public_reference', $reference)->exists());

                $model->public_reference = $reference;
            }

            if ($model->starts_at) {
                $startsAt = $model->starts_at instanceof \Carbon\CarbonInterface
                    ? $model->starts_at
                    : \Carbon\Carbon::parse($model->starts_at);

                // Temporary compatibility sync until legacy date/time columns are
                // removed after the final schema-consolidation verification.
                $model->date ??= $startsAt->toDateString();
                $model->time_slot ??= $startsAt->format('H:i');
            }
        });

        static::deleting(function (self $appointment): void {
            $appointment->queue()?->delete();
        });

        static::updating(function (self $appointment): void {
            if ($appointment->isDirty('status') && $appointment->queue) {
                $newStatus = $appointment->status;
                $queue     = $appointment->queue;

                if ($newStatus === 'cancelled') {
                    self::withoutEvents(fn () => $queue->update(['status' => 'skipped']));
                } elseif ($newStatus === 'completed') {
                    self::withoutEvents(fn () => $queue->update(['status' => 'completed']));
                } elseif ($newStatus === 'confirmed' && in_array($queue->status, ['cancelled', 'skipped'], true)) {
                    self::withoutEvents(fn () => $queue->update(['status' => 'waiting']));
                }
            }

            if ($appointment->isDirty('status')) {
                try {
                    AppointmentStatusHistory::create([
                        'appointment_id' => $appointment->id,
                        'from_status'    => $appointment->getOriginal('status'),
                        'to_status'      => $appointment->status,
                        'changed_by'     => auth()->id(),
                        'actor_type'     => auth()->check() ? 'user' : 'system',
                    ]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('StatusHistory: ' . $e->getMessage());
                }
            }
        });

        static::updated(function (self $appointment): void {
            if (! $appointment->wasChanged('status')) {
                return;
            }

            $newStatus = $appointment->status;
            $customer  = $appointment->customer;
            $startsAt  = $appointment->starts_at;
            $dateLabel = $startsAt?->format('Y-m-d') ?? '—';
            $timeLabel = $startsAt?->format('H:i') ?? '—';

            if ($customer?->user_id) {
                $messages = [
                    'confirmed' => [
                        'ar' => 'تم تأكيد موعدك بتاريخ ' . $dateLabel . ' الساعة ' . $timeLabel,
                        'en' => 'Your appointment on ' . $dateLabel . ' at ' . $timeLabel . ' has been confirmed.',
                    ],
                    'cancelled' => [
                        'ar' => 'تم إلغاء موعدك بتاريخ ' . $dateLabel,
                        'en' => 'Your appointment on ' . $dateLabel . ' has been cancelled.',
                    ],
                    'completed' => [
                        'ar' => 'تمت خدمتك بنجاح، شكراً لزيارتك!',
                        'en' => 'Your appointment has been completed. Thank you for your visit!',
                    ],
                ];

                if (isset($messages[$newStatus])) {
                    $locale  = app()->getLocale();
                    $message = $messages[$newStatus][$locale] ?? $messages[$newStatus]['en'];

                    try {
                        Notification::create([
                            'user_id'        => $customer->user_id,
                            'appointment_id' => $appointment->id,
                            'type'           => 'status_change',
                            'message'        => $message,
                            'is_read'        => false,
                        ]);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Auto-notification failed for appointment ' . $appointment->id . ': ' . $e->getMessage());
                    }
                }
            }

            if ($newStatus === 'completed' && $customer) {
                try {
                    $service = $appointment->service;
                    $price   = $service?->price ?? 0;

                    if ($price > 0) {
                        Invoice::create([
                            'customer_id'    => $customer->id,
                            'appointment_id' => $appointment->id,
                            'amount'         => $price,
                            'status'         => 'pending',
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Auto-invoice failed for appointment ' . $appointment->id . ': ' . $e->getMessage());
                }
            }
        });
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id_new'); }
    public function staff(): BelongsTo { return $this->belongsTo(Staff::class, 'staff_id_new'); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function resource(): BelongsTo { return $this->belongsTo(Resource::class); }
    public function recurringRule(): BelongsTo { return $this->belongsTo(RecurringRule::class, 'recurring_id'); }
    public function queue() { return $this->hasOne(Queue::class); }
    public function statusHistory(): HasMany { return $this->hasMany(AppointmentStatusHistory::class)->orderBy('created_at'); }
    public function reminders(): HasMany { return $this->hasMany(ReminderLog::class); }

    public function scopeToday($query)
    {
        return $query->whereDate('starts_at', today());
    }

    public function scopeUpcoming($query)
    {
        return $query
            ->where('starts_at', '>=', now())
            ->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_COMPLETED]);
    }

    public function scopePending($query) { return $query->where('status', self::STATUS_PENDING); }
    public function scopeConfirmed($query) { return $query->where('status', self::STATUS_CONFIRMED); }
    public function scopeInQueue($query) { return $query->whereHas('queue', fn ($q) => $q->whereIn('status', ['waiting', 'serving'])); }
    public function canBeAddedToQueue(): bool { return ! $this->queue && ! in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_COMPLETED], true); }

    public function isOverdue(): bool
    {
        return $this->starts_at !== null
            && $this->starts_at->isPast()
            && ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true);
    }

    public function isSoon(): bool
    {
        if (! $this->starts_at) {
            return false;
        }

        $now = now();

        return $this->starts_at->isToday()
            && $this->starts_at->between($now, $now->copy()->addHours(2));
    }

    public function getServiceNameAttribute()
    {
        return $this->service
            ? (app()->getLocale() === 'ar' && $this->service->name_ar ? $this->service->name_ar : $this->service->name)
            : $this->service_type;
    }

    public function canTransitionTo(string $newStatus): bool { return in_array($newStatus, self::VALID_TRANSITIONS[$this->status] ?? [], true); }
    public function getNetPriceAttribute(): float { return max(0, (float) $this->price - (float) $this->discount_amount); }
    public function scopeOnDate($query, \Carbon\Carbon $date) { return $query->whereDate('starts_at', $date->toDateString()); }
    public function scopeForStaff($query, int $staffId) { return $query->where('staff_id_new', $staffId); }
    public function scopeForNewCustomer($query, int $customerId) { return $query->where('customer_id_new', $customerId); }
}
