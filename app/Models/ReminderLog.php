<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderLog extends Model
{
    public $timestamps = false;

    protected $table = 'reminder_logs';

    protected $fillable = [
        'appointment_id', 'rule_id', 'channel', 'recipient',
        'status', 'gateway_message_id', 'scheduled_at', 'sent_at', 'error',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ReminderRule::class, 'rule_id');
    }
}
