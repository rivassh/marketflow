<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'watch_id',
        'workflow_id',
        'observation_id',
        'event_key',
        'state',
        'triggered_at',
        'resolved_at',
        'payload',
    ];

    protected $casts = [
        'watch_id' => 'integer',
        'workflow_id' => 'integer',
        'observation_id' => 'integer',
        'event_key' => 'string',
        'state' => 'string',
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
        'payload' => 'array',
    ];

    public function watch(): BelongsTo
    {
        return $this->belongsTo(Watch::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Watch::class, 'workflow_id');
    }

    public function observation(): BelongsTo
    {
        return $this->belongsTo(Observation::class);
    }

    public static function states(): array
    {
        return [
            'condition_not_met',
            'condition_met',
            'notification_pending',
            'notification_sent',
            'notification_failed',
            'resolved',
        ];
    }

    public function isTerminal(): bool
    {
        return in_array($this->state, ['notification_sent', 'notification_failed', 'resolved'], true);
    }

    public function transitionTo(string $newState): void
    {
        $validStates = self::states();
        if (!in_array($newState, $validStates, true)) {
            throw new \InvalidArgumentException("Invalid state: {$newState}");
        }

        $this->update(['state' => $newState]);
    }

    public function markConditionMet(): void
    {
        $this->update(['state' => 'condition_met']);
    }

    public function markNotificationPending(): void
    {
        $this->update(['state' => 'notification_pending']);
    }

    public function markNotificationSent(): void
    {
        $this->update(['state' => 'notification_sent']);
    }

    public function markNotificationFailed(string $reason): void
    {
        $this->update([
            'state' => 'notification_failed',
            'failure_reason' => $reason,
        ]);
    }

    public function markResolved(): void
    {
        $this->update(['state' => 'resolved', 'resolved_at' => now()]);
    }
}