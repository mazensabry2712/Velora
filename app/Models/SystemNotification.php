<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SystemNotification extends Model
{
    protected $fillable = [
        'title',
        'message',
        'type',
        'target',
        'tenant_ids',
        'scheduled_at',
        'is_sent',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'tenant_ids' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'is_sent' => 'boolean',
    ];

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mark as sent
     */
    public function markAsSent()
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }
}
