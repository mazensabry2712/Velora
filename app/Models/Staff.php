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
        'user_id', 'first_name', 'last_name', 'email', 'phone', 'phone_country',
        'avatar', 'title', 'bio', 'color', 'sort_order', 'timezone',
        'accepts_bookings', 'is_active', 'commission_type', 'commission_value', 'metadata',
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

    // ── Relationships ────────────────────────────────────────────────────

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

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBookable($query)
    {
        return $query->where('is_active', true)->where('accepts_bookings', true);
    }

    // ── Accessors ────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->trans('title') ?? $this->full_name;
    }

    /**
     * Get working hours for a specific day of week (0=Sunday, 6=Saturday).
     */
    public function getWorkingHoursForDay(int $dayOfWeek): ?StaffWorkingHours
    {
        return $this->workingHours->firstWhere('day_of_week', $dayOfWeek);
    }
}
