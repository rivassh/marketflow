<?php

namespace App\Providers\Processors;

use App\Providers\DTOs\NormalizedProductData;
use App\Providers\DTOs\ProductReference;
use App\Providers\DTOs\ProviderProductSnapshot;
use App\Providers\Interfaces\MarketplaceProvider;
use App\Providers\ScrapingHelper;

class DivarProcessor implements MarketplaceProvider
{
    protected array $config;

    public function __construct(
        protected ScrapingHelper $scraper,
        ?array $config = null
    ) {
        $this->config = $config ?? config('marketflow.divar', []);
    }

    public function supports($url): bool
    {
        return (bool) preg_match(
            '|^https?://divar\.ir/v/[\w-]+/[\w-]+|i',
            (string) $url
        );
    }

    public function parseProductReference($url): ProductReference
    {
        preg_match(
            '/^https?:\/\/divar\.ir\/v\/([^\/]+)\/([^\/?]+)/',
            (string) $url,
            $matches
        );

        $slug = $matches[1] ?? '';
        $token = $matches[2] ?? '';

        return ProductReference::create(
            'divar',
            $token,
            $slug
        );
    }

    public function fetchProduct(ProductReference $reference): ProviderProductSnapshot
    {
        $url = $this->buildUrl($reference);

        if ($this->config['scrape_html'] ?? false) {
            $html = $this->scraper->fetchHtml(
                $url,
                $this->config['user_agent'] ?? 'MarketFlow/3.0',
                $this->config['accept'] ?? 'text/html',
                $this->config['api_timeout'] ?? 10
            );

            return ProviderProductSnapshot::create(
                $reference->provider,
                $reference->externalId,
                $html
            );
        }

        // Divar has no public API. If scraping is disabled, throw.
        throw new \Exception(
            'Divar has no public API. Enable scrape_html in config to use this provider.'
        );
    }

    public function normalizeSnapshot(ProviderProductSnapshot $snapshot): NormalizedProductData
    {
        $raw = json_decode($snapshot->rawData, true);

        if (! is_array($raw)) {
            $raw = $this->scraper->extract(
                $snapshot->rawData,
                $this->config['selectors'] ?? []
            );
        }

        return NormalizedProductData::create([
            'provider' => $snapshot->provider,
            'externalId' => $snapshot->externalId,
            'title' => $raw['title'] ?? null,
            'price' => [
                'amount' => $raw['price'] ?? 0,
                'currency' => 'IRR',
                'display' => $raw['price_display'] ?? null,
            ],
            'availability' => $raw['availability'] ?? 'unknown',
            'stockStatus' => $raw['stock_status'] ?? null,
            'seller' => isset($raw['seller']) ? ['name' => $raw['seller']] : null,
            'badges' => $raw['badges'] ?? [],
            'imageUrl' => $raw['image'] ?? null,
            'productUrl' => $raw['product_url'] ?? null,
            'city' => $raw['city'] ?? null,
            'category' => $raw['category'] ?? null,
            'postedAt' => $raw['posted_at'] ?? null,
            'observedAt' => $raw['posted_at'] ?? null,
            'raw' => $raw,
        ]);
    }

    public function getProviderName(): string
    {
        return 'divar';
    }

    protected function buildUrl(ProductReference $reference): string
    {
        $base = $this->config['base_url'] ?? 'https://divar.ir';

        return $base.'/v/'.$reference->canonicalUrl.'/'.$reference->externalId;
    }
}
