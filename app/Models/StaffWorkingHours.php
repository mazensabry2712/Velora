<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffWorkingHours extends Model
{
    protected $table = 'staff_working_hours';

    protected $fillable = [
        'staff_id', 'day_of_week', 'start_time', 'end_time', 'is_working',
    ];

    protected $casts = [
        'is_working'  => 'boolean',
        'day_of_week' => 'integer',
    ];

    protected $appends = [
        'day_name',
        'day_name_ar',
        'formatted_start_time',
        'formatted_end_time',
        'localized_day_name',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function getDayNameAttribute(): string
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ][$this->day_of_week] ?? '';
    }

    public function getDayNameArAttribute(): string
    {
        return [
            0 => 'الأحد',
            1 => 'الإثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
        ][$this->day_of_week] ?? '';
    }

    public function getFormattedStartTimeAttribute(): string
    {
        return $this->formatTime($this->start_time);
    }

    public function getFormattedEndTimeAttribute(): string
    {
        return $this->formatTime($this->end_time);
    }

    public function getLocalizedDayNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->day_name_ar : $this->day_name;
    }

    private function formatTime(?string $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        $timestamp = strtotime($time);

        return $timestamp === false ? $time : date('h:i A', $timestamp);
    }
}
