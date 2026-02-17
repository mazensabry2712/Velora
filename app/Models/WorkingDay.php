<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkingDay extends Model
{
    protected $fillable = [
        'day_of_week',
        'day_name',
        'day_name_ar',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Get localized name
    public function getLocalizedNameAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->day_name_ar : $this->day_name;
    }
}
