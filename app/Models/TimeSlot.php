<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    protected $fillable = [
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['formatted_start_time', 'formatted_end_time', 'formatted_time'];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Get formatted start time
    public function getFormattedStartTimeAttribute()
    {
        return date('h:i A', strtotime($this->start_time));
    }

    // Get formatted end time
    public function getFormattedEndTimeAttribute()
    {
        return date('h:i A', strtotime($this->end_time));
    }

    // Get formatted time range
    public function getFormattedTimeAttribute()
    {
        return $this->formatted_start_time . ' - ' . $this->formatted_end_time;
    }

    public function getDisplayTimeAttribute()
    {
        return date('H:i', strtotime($this->start_time));
    }
}
