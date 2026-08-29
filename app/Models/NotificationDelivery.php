<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class NotificationDelivery extends Model
{
    protected $fillable = [
        'appointment_id',
        'public_reference',
        'event',
        'channel',
        'recipient',
        'provider',
        'status',
        'attempts',
        'dedupe_key',
        'last_error',
        'queued_at',
        'sent_at',
        'failed_at',
        'metadata',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];
}
