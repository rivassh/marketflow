<?php

namespace App\Providers\Interfaces;

use App\Providers\DTOs\{ProductReference, ProviderProductSnapshot, NormalizedProductData};

interface MarketplaceProvider
{
    /**
     * Check if this provider supports the given product URL
     */
    public function supports($url): bool;

    /**
     * Parse product reference from URL
     */
    public function parseProductReference($url): ProductReference;

    /**
     * Fetch product data from provider
     */
    public function fetchProduct(ProductReference $reference): ProviderProductSnapshot;

    /**
     * Normalize provider response to common structure
     */
    public function normalizeSnapshot(ProviderProductSnapshot $snapshot): NormalizedProductData;

    /**
     * Get provider name
     */
    public function getProviderName(): string;
}