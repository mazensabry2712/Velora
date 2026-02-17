<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffSchedule extends Model
{
    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['day_name', 'day_name_ar', 'formatted_start_time', 'formatted_end_time'];

    // Days mapping
    const DAYS = [
        0 => ['en' => 'Sunday', 'ar' => 'الأحد'],
        1 => ['en' => 'Monday', 'ar' => 'الإثنين'],
        2 => ['en' => 'Tuesday', 'ar' => 'الثلاثاء'],
        3 => ['en' => 'Wednesday', 'ar' => 'الأربعاء'],
        4 => ['en' => 'Thursday', 'ar' => 'الخميس'],
        5 => ['en' => 'Friday', 'ar' => 'الجمعة'],
        6 => ['en' => 'Saturday', 'ar' => 'السبت'],
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    // Accessors
    public function getDayNameAttribute()
    {
        return self::DAYS[$this->day_of_week]['en'] ?? '';
    }

    public function getDayNameArAttribute()
    {
        return self::DAYS[$this->day_of_week]['ar'] ?? '';
    }

    public function getFormattedStartTimeAttribute()
    {
        return date('h:i A', strtotime($this->start_time));
    }

    public function getFormattedEndTimeAttribute()
    {
        return date('h:i A', strtotime($this->end_time));
    }

    public function getLocalizedDayNameAttribute()
    {
        return app()->getLocale() === 'ar' ? $this->day_name_ar : $this->day_name;
    }
}
