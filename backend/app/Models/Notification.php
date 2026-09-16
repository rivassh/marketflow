<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

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
        'user_id' => 'integer',
        'watch_id' => 'integer',
        'alert_event_id' => 'integer',
        'channel' => 'string',
        'recipient' => 'string',
        'template' => 'string',
        'payload' => 'array',
        'status' => 'string',
        'provider_message_id' => 'string',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'failure_reason' => 'string',
        'attempts' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function watch()
    {
        return $this->belongsTo(Watch::class);
    }

    public function alertEvent()
    {
        return $this->belongsTo(AlertEvent::class);
    }

    public function states(): array
    {
        return [
            'pending',
            'sent',
            'failed',
            'cancelled',
        ];
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['sent', 'failed', 'cancelled'], true);
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
        $this->save();
    }

    public function markAsSent(string $providerMessageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'provider_message_id' => $providerMessageId,
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $failureReason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $failureReason,
            'failed_at' => now(),
        ]);
    }
}