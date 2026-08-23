<?php

namespace App\Providers\DTOs;

class ProviderProductSnapshot
{
    public function __construct(
        public readonly string $provider,
        public readonly string $externalId,
        public readonly string $rawData,
        public readonly string $contentHash
    ) {}

    public static function create(string $provider, string $externalId, string $rawData): self
    {
        return new self(
            $provider,
            $externalId,
            $rawData,
            md5($rawData)
        );
    }
}