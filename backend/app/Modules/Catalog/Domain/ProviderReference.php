<?php

namespace App\Modules\Catalog\Domain;

class ProviderReference
{
    private string $provider;

    private string $externalId;

    private string $canonicalUrl;

    public function __construct(string $provider, string $externalId, string $canonicalUrl)
    {
        if (empty($provider)) {
            throw new \InvalidArgumentException('Provider cannot be empty');
        }
        if (empty($externalId)) {
            throw new \InvalidArgumentException('External ID cannot be empty');
        }
        if (! filter_var($canonicalUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid canonical URL');
        }

        $this->provider = $provider;
        $this->externalId = $externalId;
        $this->canonicalUrl = $canonicalUrl;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getCanonicalUrl(): string
    {
        return $this->canonicalUrl;
    }

    public function equals(ProviderReference $other): bool
    {
        return $this->provider === $other->provider
            && $this->externalId === $other->externalId;
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'external_id' => $this->externalId,
            'canonical_url' => $this->canonicalUrl,
        ];
    }
}
