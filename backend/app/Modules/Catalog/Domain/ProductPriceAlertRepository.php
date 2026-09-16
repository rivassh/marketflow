<?php

namespace App\Modules\Catalog\Domain;

use App\Modules\Shared\Domain\RepositoryInterface;
use App\Models\ProductPriceAlert;

class ProductPriceAlertRepository extends RepositoryInterface
{
    public function find(int $id): ?ProductPriceAlert
    {
        $model = ProductPriceAlert::withTrashed()->find($id);
        return $model ? $this->hydrate($model) : null;
    }

    public function findAll(): array
    {
        $models = ProductPriceAlert::all();
        return array_map([$this, 'hydrate'], $models->toArray());
    }

    public function save(object $entity): void
    {
        if (!$entity instanceof ProductPriceAlert) {
            throw new \InvalidArgumentException('Entity must be ProductPriceAlert instance');
        }
        
        $data = $entity->toArray();
        unset($data['id']);
        unset($data['created_at']);
        unset($data['updated_at']);
        
        ProductPriceAlert::updateOrCreate(
            ['id' => $entity->getId()],
            $data
        );
    }

    public function delete(int $id): void
    {
        ProductPriceAlert::find($id)->delete();
    }

    private function hydrate(ProductPriceAlert $model): ProductPriceAlert
    {
        $data = $model->toArray();
        
        return new ProductPriceAlert([
            'id' => $data['id'],
            'product_id' => $data['product_id'],
            'min_price' => $data['min_price'] ?? null,
            'max_price' => $data['max_price'] ?? null,
            'currency' => $data['currency'] ?? 'IRR',
            'availability' => $data['availability'] ?? null,
            'stock_status' => $data['stock_status'] ?? null,
            'seller_name' => $data['seller_name'] ?? null,
            'is_active' => $data['is_active'],
            'redis_ttl' => $data['redis_ttl'] ?? 300,
            'last_notified_at' => $data['last_notified_at'],
            'alert_conditions' => $data['alert_conditions'] ?? [],
        ]);
    }
}