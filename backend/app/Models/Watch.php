<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Workflow\WorkflowType;

class Watch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_url',
        'product_ref',
        'workflow_type',
        'conditions',
        'frequency_minutes',
        'user_id',
        'is_active',
        'last_checked_at',
        'is_tracked',
        'alert_count',
        'price_history',
    ];

    protected $casts = [
        'conditions' => 'array',
        'frequency_minutes' => 'integer',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'is_tracked' => 'boolean',
        'alert_count' => 'integer',
        'price_history' => 'array',
        'product_ref' => 'array',
        'workflow_type' => WorkflowType::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }

    public function alertEvents(): HasMany
    {
        return $this->hasMany(AlertEvent::class)->orderBy('triggered_at', 'desc');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function shouldCheck(): bool
    {
        if (!$this->is_active || !$this->is_tracked) {
            return false;
        }

        return !$this->last_checked_at ||
               $this->last_checked_at->addMinutes($this->frequency_minutes)->isPast();
    }

    public function getConfig(): array
    {
        return $this->conditions ?? [];
    }

    public function shouldSendNotification(): bool
    {
        $latest = $this->latestAlertEvent();

        // No prior events — always allow
        if (!$latest) {
            return true;
        }

        // If the latest event was resolved, allow re-trigger
        if ($latest->state === 'resolved') {
            return true;
        }

        // Check cooldown: if last notification was sent more than cooldown minutes ago, allow
        if ($latest->state === 'notification_sent') {
            $cooldownMinutes = config('watch.notification_cooldown_minutes', 15);
            $lastSent = $latest->triggered_at ?? now();

            return now()->subMinutes($cooldownMinutes) > $lastSent;
        }

        // For other states (failed, pending), allow re-try
        return true;
    }

    public function latestAlertEvent(): ?AlertEvent
    {
        return $this->alertEvents()->first();
    }

    public function getEventKey(string $conditionState): string
    {
        return sha256(
            $this->id .
            $this->workflow_type?->value ?? '' .
            $conditionState .
            $this->last_checked_at?->toIso8601String() ?? ''
        );
    }
}