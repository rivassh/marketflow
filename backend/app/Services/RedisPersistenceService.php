<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RedisPersistenceService
{
    protected string $prefix = 'marketflow:';

    /**
     * Store product price alert in Redis with TTL
     */
    public function storePriceAlert(int $productId, array $alertData, int $ttl = 300): void
    {
        $key = $this->prefix."price_alert:{$productId}";

        Redis::setex(
            $key,
            $ttl,
            json_encode($alertData, JSON_UNESCAPED_SLASHES)
        );

        Log::info('Price alert stored in Redis', [
            'product_id' => $productId,
            'key' => $key,
            'ttl' => $ttl,
        ]);
    }

    /**
     * Retrieve price alert from Redis cache
     */
    public function getPriceAlert(int $productId): ?array
    {
        $key = $this->prefix."price_alert:{$productId}";
        $cached = Redis::get($key);

        if ($cached) {
            return json_decode($cached, true);
        }

        return null;
    }

    /**
     * Store normalized product data in Redis
     */
    public function storeNormalizedProduct(int $productId, array $normalizedData, int $ttl = 600): void
    {
        $key = $this->prefix."product:{$productId}";

        Redis::setex(
            $key,
            $ttl,
            json_encode($normalizedData, JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Retrieve normalized product data from Redis
     */
    public function getNormalizedProduct(int $productId): ?array
    {
        $key = $this->prefix."product:{$productId}";
        $cached = Redis::get($key);

        if ($cached) {
            return json_decode($cached, true);
        }

        return null;
    }

    /**
     * Store price history in Redis sorted set
     */
    public function appendPriceHistory(int $productId, int $price, ?int $timestamp = null): void
    {
        $key = $this->prefix."price_history:{$productId}";
        $score = $timestamp ?? time();

        Redis::zadd($key, $score, json_encode([
            'price' => $price,
            'timestamp' => $timestamp ?? time(),
        ]));

        // Keep only last 1000 entries
        Redis::zremrangebyrank($key, 0, -1001);
    }

    /**
     * Get price history for a product
     */
    public function getPriceHistory(int $productId, int $limit = 100): array
    {
        $key = $this->prefix."price_history:{$productId}";
        $entries = Redis::zrevrange($key, 0, $limit - 1, ['WITHSCORES' => true]);

        $history = [];
        foreach ($entries as $member => $score) {
            $data = json_decode($member, true);
            $data['timestamp'] = $score;
            $history[] = $data;
        }

        return $history;
    }

    /**
     * Invalidate product cache
     */
    public function invalidateProductCache(int $productId): void
    {
        $keys = [
            $this->prefix."price_alert:{$productId}",
            $this->prefix."product:{$productId}",
            $this->prefix."price_history:{$productId}",
        ];

        foreach ($keys as $key) {
            Redis::del($key);
        }

        Log::info('Product cache invalidated', ['product_id' => $productId]);
    }

    /**
     * Set product processing flag to prevent duplicate processing
     */
    public function acquireProcessingLock(int $productId, int $ttl = 30): bool
    {
        $key = $this->prefix."processing:{$productId}";
        $result = Redis::set($key, '1', 'NX', 'EX', $ttl);

        return $result !== null;
    }

    /**
     * Release processing lock
     */
    public function releaseProcessingLock(int $productId): void
    {
        $key = $this->prefix."processing:{$productId}";
        Redis::del($key);
    }

    /**
     * Check if product is currently being processed
     */
    public function isProcessing(int $productId): bool
    {
        $key = $this->prefix."processing:{$productId}";

        return (bool) Redis::exists($key);
    }

    /**
     * Store the last check timestamp for a watch
     */
    public function updateWatchLastCheck(int $watchId, ?int $timestamp = null): void
    {
        $key = $this->prefix."watch:last_check:{$watchId}";
        Redis::set($key, $timestamp ?? time());
    }

    /**
     * Get the last check timestamp for a watch
     */
    public function getWatchLastCheck(int $watchId): ?int
    {
        $key = $this->prefix."watch:last_check:{$watchId}";
        $value = Redis::get($key);

        return $value ? (int) $value : null;
    }

    /**
     * Cache alert notification status
     */
    public function markAlertNotified(int $alertId, ?int $timestamp = null): void
    {
        $key = $this->prefix."alert:notified:{$alertId}";
        Redis::set($key, $timestamp ?? time());
    }

    /**
     * Check if alert was already notified
     */
    public function wasAlertNotified(int $alertId): bool
    {
        $key = $this->prefix."alert:notified:{$alertId}";

        return (bool) Redis::exists($key);
    }
}
