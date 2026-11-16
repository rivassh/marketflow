<?php

namespace Tests\Unit;

use App\Services\RedisPersistenceService;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RedisPersistenceServiceTest extends TestCase
{
    protected RedisPersistenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RedisPersistenceService;
    }

    public function test_service_uses_marketflow_prefix(): void
    {
        $reflection = new \ReflectionClass(RedisPersistenceService::class);
        $property = $reflection->getProperty('prefix');
        $property->setAccessible(true);

        $this->assertEquals('marketflow:', $property->getValue($this->service));
    }

    public function test_store_price_alert_calls_setex_with_correct_args(): void
    {
        Redis::shouldReceive('setex')
            ->once()
            ->withArgs(function ($key, $ttl, $value) {
                $data = json_decode($value, true);

                return $key === 'marketflow:price_alert:123'
                    && $ttl === 300
                    && $data['price'] === 9500000;
            });

        $this->service->storePriceAlert(123, ['price' => 9500000], 300);
    }

    public function test_get_price_alert_returns_decoded_data_when_key_exists(): void
    {
        Redis::shouldReceive('get')
            ->with('marketflow:price_alert:123')
            ->andReturn('{"price":9500000}');

        $result = $this->service->getPriceAlert(123);
        $this->assertEquals(['price' => 9500000], $result);
    }

    public function test_get_price_alert_returns_null_when_key_missing(): void
    {
        Redis::shouldReceive('get')
            ->with('marketflow:price_alert:999')
            ->andReturn(null);

        $result = $this->service->getPriceAlert(999);
        $this->assertNull($result);
    }

    public function test_store_normalized_product_calls_setex(): void
    {
        Redis::shouldReceive('setex')
            ->once()
            ->withArgs(function ($key, $ttl, $value) {
                return $key === 'marketflow:product:123'
                    && $ttl === 600;
            });

        $this->service->storeNormalizedProduct(123, ['title' => 'Test'], 600);
    }

    public function test_invalidate_product_cache_deletes_all_keys(): void
    {
        // The service calls Redis::del in a loop, 3 times
        Redis::shouldReceive('del')
            ->with('marketflow:price_alert:123')
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('del')
            ->with('marketflow:product:123')
            ->once()
            ->andReturn(1);

        Redis::shouldReceive('del')
            ->with('marketflow:price_history:123')
            ->once()
            ->andReturn(1);

        $this->service->invalidateProductCache(123);
    }

    public function test_append_price_history_uses_zadd(): void
    {
        Redis::shouldReceive('zadd')->once()->andReturn(1);
        Redis::shouldReceive('zremrangebyrank')
            ->with('marketflow:price_history:123', 0, -1001)
            ->once()
            ->andReturn(0);

        $this->service->appendPriceHistory(123, 9500000, 1000);
    }

    public function test_get_price_history_returns_decoded_entries(): void
    {
        $data = json_encode(['price' => 9500000, 'timestamp' => 1000]);
        Redis::shouldReceive('zrevrange')
            ->with('marketflow:price_history:123', 0, 9, ['WITHSCORES' => true])
            ->andReturn([$data => '1000']);

        $result = $this->service->getPriceHistory(123, 10);
        $this->assertCount(1, $result);
        $this->assertEquals(9500000, $result[0]['price']);
        $this->assertEquals('1000', $result[0]['timestamp']);
    }

    public function test_update_watch_last_check_stores_timestamp(): void
    {
        Redis::shouldReceive('set')
            ->with('marketflow:watch:last_check:456', 12345)
            ->once()
            ->andReturn('OK');

        $this->service->updateWatchLastCheck(456, 12345);
    }

    public function test_get_watch_last_check_returns_int_when_value_exists(): void
    {
        Redis::shouldReceive('get')
            ->with('marketflow:watch:last_check:456')
            ->andReturn('12345');

        $result = $this->service->getWatchLastCheck(456);
        $this->assertIsInt($result);
        $this->assertEquals(12345, $result);
    }

    public function test_get_watch_last_check_returns_null_when_missing(): void
    {
        Redis::shouldReceive('get')
            ->with('marketflow:watch:last_check:999')
            ->andReturn(null);

        $result = $this->service->getWatchLastCheck(999);
        $this->assertNull($result);
    }

    public function test_mark_alert_notified_stores_value(): void
    {
        Redis::shouldReceive('set')
            ->with('marketflow:alert:notified:789', 12345)
            ->once()
            ->andReturn('OK');

        $this->service->markAlertNotified(789, 12345);
    }

    public function test_was_alert_notified_uses_exists(): void
    {
        Redis::shouldReceive('exists')
            ->with('marketflow:alert:notified:789')
            ->once()
            ->andReturn(1);

        $result = $this->service->wasAlertNotified(789);
        $this->assertTrue($result);
    }
}
