<?php

namespace App\Modules\Catalog\Domain;

use App\Modules\Shared\ValueObjects\Price;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Product
{
    private ?int $id;

    private string $name;

    private string $slug;

    private ?string $brand;

    private ?string $model;

    private ?Price $currentPrice;

    private array $aliases = [];

    private array $metadata = [];

    private Carbon $createdAt;

    private Carbon $updatedAt;

    public function __construct(
        string $name,
        ?string $slug = null,
        ?string $brand = null,
        ?string $model = null
    ) {
        $this->id = null;
        $this->name = $name;
        $this->slug = $slug ?? Str::slug($name);
        $this->brand = $brand;
        $this->model = $model;
        $this->createdAt = Carbon::now();
        $this->updatedAt = Carbon::now();
    }

    public static function reconstitute(array $data): self
    {
        $product = new self(
            $data['name'],
            $data['slug'],
            $data['brand'] ?? null,
            $data['model'] ?? null
        );

        $product->id = $data['id'];

        if (isset($data['current_price'])) {
            $price = $data['current_price'];
            if (is_array($price)) {
                $product->currentPrice = new Price(
                    $price['amount'],
                    $price['currency'] ?? 'IRR'
                );
            }
        }

        $product->aliases = $data['aliases'] ?? [];
        $product->metadata = $data['metadata'] ?? [];
        $product->createdAt = isset($data['created_at']) ? Carbon::parse($data['created_at']) : Carbon::now();
        $product->updatedAt = isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : Carbon::now();

        return $product;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getCurrentPrice(): ?Price
    {
        return $this->currentPrice;
    }

    public function setPrice(Price $price): self
    {
        $this->currentPrice = $price;
        $this->updatedAt = Carbon::now();

        return $this;
    }

    public function addAlias(string $alias): self
    {
        $this->aliases[] = $alias;

        return $this;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function hasAlias(string $alias): bool
    {
        return in_array($alias, $this->aliases, true);
    }

    public function addMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    public function getMetadata(?string $key = null): mixed
    {
        return $key ? ($this->metadata[$key] ?? null) : $this->metadata;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    public function touch(): void
    {
        $this->updatedAt = Carbon::now();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'brand' => $this->brand,
            'model' => $this->model,
            'current_price' => $this->currentPrice?->toArray(),
            'aliases' => $this->aliases,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->toIso8601String(),
            'updated_at' => $this->updatedAt->toIso8601String(),
        ];
    }
}
