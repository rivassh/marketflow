<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class ProductPriceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'min_price',
        'max_price',
        'currency',
        'availability',
        'stock_status',
        'seller_name',
        'is_active',
        'redis_ttl',
        'last_notified_at',
        'alert_conditions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'redis_ttl' => 'integer',
        'last_notified_at' => 'datetime',
        'alert_conditions' => 'array',
        'min_price' => 'integer',
        'max_price' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function storeWithPersistence(array $data): self
    {
        $cacheKey = $this->getRedisCacheKey();
        $ttl = $data['redis_ttl'] ?? config('database.redis_persistence_ttl', 300);

        // Store in Redis with TTL
        Redis::setex($cacheKey, $ttl, json_encode($data, JSON_UNESCAPED_SLASHES));

        // Persist in database
        $alert = static::firstOrCreate(
            ['product_id' => $data['product_id']],
            [
                'min_price' => $data['min_price'] ?? null,
                'max_price' => $data['max_price'] ?? null,
                'currency' => $data['currency'] ?? 'IRR',
                'availability' => $data['availability'] ?? null,
                'stock_status' => $data['stock_status'] ?? null,
                'seller_name' => $data['seller_name'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'redis_ttl' => $ttl,
                'alert_conditions' => $data['alert_conditions'] ?? null,
            ]
        );

        return $alert;
    }

    public function getFromCache(): ?array
    {
        $cacheKey = $this->getRedisCacheKey();
        $cached = Redis::get($cacheKey);

        if ($cached) {
            return json_decode($cached, true);
        }

        // Fall back to database query
        return $this->toArray();
    }

    protected function getRedisCacheKey(): string
    {
        return "price_alert:product:{$this->product_id}:{$this->id}";
    }

    public function invalidateCache(): void
    {
        Redis::del($this->getRedisCacheKey());
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            $model->invalidateCache();
            $model->storeWithPersistence($model->getAttributes());
        });

        static::deleted(function ($model) {
            $model->invalidateCache();
        });
    }
}
