<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function watch()
    {
        return $this->belongsTo(Watch::class);
    }

    public function workflow()
    {
        return $this->belongsTo(Watch::class, 'workflow_id');
    }

    public function observation()
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
}