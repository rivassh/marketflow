<?php

namespace App\Providers\Processors;

use App\Providers\Interfaces\MarketplaceProvider;
use App\Providers\DTOs\{
    ProductReference,
    ProviderProductSnapshot,
    NormalizedProductData
};
use Illuminate\Support\Facades\Http;

class TorobProcessor implements MarketplaceProvider
{
    /**
     * Provider configuration
     */
    protected array $config = [
        'base_url' => 'https://torob.com',
        'api_timeout' => 10,
        'retry_count' => 2,
        'cache_ttl' => 300,
        'user_agent' => 'MarketFlow/3.0',
    ];

    /**
     * Check if this provider supports the given product URL
     */
    public function supports($url): bool
    {
        // Match torob.com product URLs (requires slug after ID)
        return preg_match(
            '|^https?://torob\.com/p/\d+[a-z0-9-]+/|i',
            $url
        ) > 0;
    }

    /**
     * Parse product reference from torob URL
     */
    public function parseProductReference($url): ProductReference
    {
        // Match torob URL pattern: https://torob.com/p/12345/smartphone-x-review/
        preg_match(
            '/^https?:\/\/torob\.com\/p\/(\\d+)\/([a-z0-9-]+)\/.*$/i',
            $url,
            $matches
        );

        return ProductReference::create(
            'torob',
            $matches[1] ?? '',
            $matches[2] ?? $url
        );
    }

    /**
     * Fetch product data from provider
     */
    public function fetchProduct(ProductReference $reference): ProviderProductSnapshot
    {
        $url = $this->buildUrl($reference);

        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->config['user_agent'],
                'Accept' => 'application/json',
            ])->timeout($this->config['api_timeout'])
              ->get($url);

            if ($response->failed()) {
                throw new \Exception('Provider request failed: ' . $response->status());
            }

            $rawData = $response->json();

            return ProviderProductSnapshot::create(
                $reference->provider,
                $reference->externalId,
                json_encode($rawData)
            );
        } catch (\Exception $e) {
            \Log::error('Torob fetch error', ['error' => $e->getMessage(), 'url' => $url]);
            throw $e;
        }
    }

    /**
     * Normalize provider response to common structure
     */
    public function normalizeSnapshot(ProviderProductSnapshot $snapshot): NormalizedProductData
    {
        $rawData = json_decode($snapshot->rawData, true) ?? [];

        // Extract data using safe JSON path reader
        $title = $this->extractTitle($rawData);
        $price = $this->extractPrice($rawData);
        $availability = $this->extractAvailability($rawData);
        $stockStatus = $this->extractStockStatus($rawData);
        $seller = $this->extractSeller($rawData);
        $badges = $this->extractBadges($rawData);
        $imageUrl = $this->extractImageUrl($rawData);
        $productUrl = $this->extractProductUrl($rawData);
        $observedAt = $this->extractObservedAt($rawData);

        return NormalizedProductData::create([
            'provider' => $snapshot->provider,
            'externalId' => $snapshot->externalId,
            'title' => $title,
            'price' => $price,
            'availability' => $availability,
            'stockStatus' => $stockStatus,
            'seller' => $seller,
            'badges' => $badges,
            'imageUrl' => $imageUrl,
            'productUrl' => $productUrl,
            'observedAt' => $observedAt,
            'raw' => $rawData
        ]);
    }

    /**
     * Get provider name
     */
    public function getProviderName(): string
    {
        return 'torob';
    }

    /**
     * Build the URL for fetching product data
     */
    protected function buildUrl(ProductReference $reference): string
    {
        $base = $this->config['base_url'];
        
        return $base . '/p/' . $reference->externalId . '/' . str_replace(' ', '-', strtolower($reference->externalId)) . '/';
    }

    /**
     * Safely extract title from raw data
     */
    protected function extractTitle(array $data): ?string
    {
        return $data['product']['title'] ?? null;
    }

    /**
     * Safely extract price from raw data
     */
    protected function extractPrice(array $data): ?array
    {
        $priceData = $data['price'] ?? [];

        return [
            'amount' => $priceData['amount'] ?? 0,
            'currency' => $priceData['currency'] ?? 'IRR',
            'display' => $priceData['display'] ?? null
        ];
    }

    /**
     * Safely extract availability from raw data
     */
    protected function extractAvailability(array $data): ?string
    {
        return $data['availability'] ?? null;
    }

    /**
     * Safely extract stock status from raw data
     */
    protected function extractStockStatus(array $data): ?string
    {
        return $data['stock_status'] ?? null;
    }

    /**
     * Safely extract seller information from raw data
     */
    protected function extractSeller(array $data): ?array
    {
        return $data['seller'] ?? null;
    }

    /**
     * Safely extract badges from raw data
     */
    protected function extractBadges(array $data): array
    {
        return $data['badges'] ?? [];
    }

    /**
     * Safely extract image URL from raw data
     */
    protected function extractImageUrl(array $data): ?string
    {
        return $data['image_url'] ?? null;
    }

    /**
     * Safely extract product URL from raw data
     */
    protected function extractProductUrl(array $data): ?string
    {
        return $data['product_url'] ?? null;
    }

    /**
     * Safely extract observed_at timestamp from raw data
     */
    protected function extractObservedAt(array $data): ?string
    {
        return $data['observed_at'] ?? null;
    }
}