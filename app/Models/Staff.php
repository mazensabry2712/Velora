<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use SoftDeletes, HasTranslations;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_country',
        'avatar',
        'title',
        'bio',
        'color',
        'sort_order',
        'timezone',
        'accepts_bookings',
        'is_active',
        'commission_type',
        'commission_value',
        'metadata',
    ];

    protected $casts = [
        'title'            => 'array',
        'bio'              => 'array',
        'metadata'         => 'array',
        'is_active'        => 'boolean',
        'accepts_bookings' => 'boolean',
        'commission_value' => 'decimal:2',
        'sort_order'       => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'staff_services', 'staff_id', 'service_id')
            ->withPivot(['override_price', 'override_duration'])
            ->withTimestamps();
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(StaffWorkingHours::class);
    }

    /** Compatibility relation for existing views/consumers; storage remains StaffWorkingHours. */
    public function activeSchedules(): HasMany
    {
        return $this->workingHours()->where('is_working', true);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(StaffBreak::class);
    }

    public function timeOff(): HasMany
    {
        return $this->hasMany(StaffTimeOff::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'staff_id_new');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBookable($query)
    {
        return $query->where('is_active', true)->where('accepts_bookings', true);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** Read-only compatibility accessor; the Staff entity remains the source of truth. */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    /** Read-only compatibility accessor for consumers still displaying specialization. */
    public function getSpecializationAttribute(): ?string
    {
        return $this->trans('title') ?: $this->user?->specialization;
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->trans('title') ?? $this->full_name;
    }

    public function getWorkingHoursForDay(int $dayOfWeek): ?StaffWorkingHours
    {
        return $this->workingHours->firstWhere('day_of_week', $dayOfWeek);
    }
}
