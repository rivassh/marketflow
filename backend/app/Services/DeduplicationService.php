<?php

namespace App\Services;

use App\Models\AlertEvent;
use App\Models\Watch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DeduplicationService
{
    protected int $dailyLimit;

    protected int $cooldownMinutes;

    public function __construct()
    {
        $this->dailyLimit = config('watch.daily_notification_limit', 10);
        $this->cooldownMinutes = config('watch.notification_cooldown_minutes', 15);
    }

    public function shouldSendNotification(Watch $watch): bool
    {
        if (! $watch->shouldSendNotification()) {
            return false;
        }

        if ($this->hasReachedDailyLimit($watch)) {
            Log::info('Daily notification limit reached', ['watch_id' => $watch->id]);

            return false;
        }

        if ($this->isInCooldown($watch)) {
            Log::info('Notification in cooldown period', ['watch_id' => $watch->id]);

            return false;
        }

        return true;
    }

    public function isDuplicate(string $eventKey): bool
    {
        return Cache::has("alert:dup:{$eventKey}") ||
               AlertEvent::where('event_key', $eventKey)->exists();
    }

    public function markDuplicate(string $eventKey): void
    {
        Cache::put("alert:dup:{$eventKey}", true, now()->addMinutes($this->cooldownMinutes));
    }

    public function createAlertEvent(Watch $watch, string $state, array $payload = []): AlertEvent
    {
        $eventKey = $watch->getEventKey($state);

        if ($this->isDuplicate($eventKey)) {
            Log::info('Duplicate alert event skipped', [
                'watch_id' => $watch->id,
                'event_key' => $eventKey,
            ]);
            throw new \Exception("Duplicate alert event: {$eventKey}");
        }

        $alertEvent = AlertEvent::create([
            'watch_id' => $watch->id,
            'event_key' => $eventKey,
            'state' => $state,
            'triggered_at' => now(),
            'payload' => $payload,
        ]);

        $this->markDuplicate($eventKey);

        return $alertEvent;
    }

    public function getTodayNotificationCount(int $userId): int
    {
        $todayStart = now()->startOfDay();

        return AlertEvent::whereHas('watch', fn ($q) => $q->where('user_id', $userId))
            ->where('triggered_at', '>=', $todayStart)
            ->where('state', 'notification_sent')
            ->count();
    }

    protected function hasReachedDailyLimit(Watch $watch): bool
    {
        $count = $this->getTodayNotificationCount($watch->user_id);

        return $count >= $this->dailyLimit;
    }

    protected function isInCooldown(Watch $watch): bool
    {
        $latest = $watch->latestAlertEvent();
        if (! $latest || $latest->state !== 'notification_sent') {
            return false;
        }

        return now()->diffInMinutes($latest->triggered_at) < $this->cooldownMinutes;
    }
}
