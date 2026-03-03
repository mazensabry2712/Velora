<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffBreak extends Model
{
    protected $table = 'staff_breaks';

    protected $fillable = [
        'staff_id', 'day_of_week', 'start_time', 'end_time', 'label',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
