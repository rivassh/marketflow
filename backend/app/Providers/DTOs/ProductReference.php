<?php

namespace App\Providers\DTOs;

class ProductReference
{
    public function __construct(
        public readonly string $provider,
        public readonly string $externalId,
        public readonly string $canonicalUrl
    ) {}

    public static function create(string $provider, string $externalId, string $canonicalUrl): self
    {
        return new self($provider, $externalId, $canonicalUrl);
    }
}