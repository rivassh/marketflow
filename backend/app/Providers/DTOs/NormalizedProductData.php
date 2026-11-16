<?php

namespace App\Providers\DTOs;

class NormalizedProductData
{
    public function __construct(
        public readonly ?string $provider = null,
        public readonly ?string $externalId = null,
        public readonly ?string $title = null,
        public readonly ?array $price = null,
        public readonly ?string $availability = null,
        public readonly ?string $stockStatus = null,
        public readonly ?array $seller = null,
        public readonly ?array $badges = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $productUrl = null,
        public readonly ?string $observedAt = null,
        public readonly ?array $raw = null
    ) {}

    public static function create(array $data): self
    {
        return new self(...$data);
    }
}
