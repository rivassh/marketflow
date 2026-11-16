<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'watch_id',
        'alert_event_id',
        'channel',
        'recipient',
        'template',
        'payload',
        'status',
        'provider_message_id',
        'sent_at',
        'failed_at',
        'failure_reason',
        'attempts',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function watch(): BelongsTo
    {
        return $this->belongsTo(Watch::class);
    }

    public function alertEvent(): BelongsTo
    {
        return $this->belongsTo(AlertEvent::class);
    }

    public function markAsSent(?string $providerMessageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'provider_message_id' => $providerMessageId,
            'sent_at' => now(),
            'failed_at' => null,
            'failure_reason' => null,
        ]);
    }

    public function markAsFailed(string $failureReason): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $failureReason,
            'attempts' => $this->attempts + 1,
        ]);
    }
}
