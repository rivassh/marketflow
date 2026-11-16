<?php

namespace App\Providers\Processors;

use App\Providers\DTOs\NormalizedProductData;
use App\Providers\DTOs\ProductReference;
use App\Providers\DTOs\ProviderProductSnapshot;
use App\Providers\Interfaces\MarketplaceProvider;
use App\Providers\ScrapingHelper;
use Illuminate\Support\Facades\Http;

class TorobProcessor implements MarketplaceProvider
{
    protected array $config;

    public function __construct(
        protected ?ScrapingHelper $scraper = null,
        ?array $config = null
    ) {
        $this->config = $config ?? config('marketflow.torob', []);
    }

    protected function scraper(): ScrapingHelper
    {
        return $this->scraper ?? app(ScrapingHelper::class);
    }

    public function supports($url): bool
    {
        return (bool) preg_match(
            '|^https?://torob\.com/p/\d+[a-z0-9-]+/|i',
            (string) $url
        );
    }

    public function parseProductReference($url): ProductReference
    {
        preg_match(
            '/^https?:\/\/torob\.com\/p\/(\d+)\/([a-z0-9-]+)\/.*$/i',
            (string) $url,
            $matches
        );

        $externalId = $matches[1] ?? '';
        $slug = $matches[2] ?? '';

        return ProductReference::create(
            'torob',
            $externalId,
            $slug
        );
    }

    public function fetchProduct(ProductReference $reference): ProviderProductSnapshot
    {
        $url = $this->buildUrl($reference);

        if ($this->config['scrape_html'] ?? true) {
            $html = $this->scraper()->fetchHtml(
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

        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->config['user_agent'] ?? 'MarketFlow/3.0',
                'Accept' => 'application/json',
            ])->timeout($this->config['api_timeout'] ?? 10)
                ->get($url);

            if ($response->failed()) {
                throw new \Exception('Provider request failed: '.$response->status());
            }

            return ProviderProductSnapshot::create(
                $reference->provider,
                $reference->externalId,
                json_encode($response->json())
            );
        } catch (\Exception $e) {
            \Log::error('Torob fetch error', ['error' => $e->getMessage(), 'url' => $url]);
            throw $e;
        }
    }

    public function normalizeSnapshot(ProviderProductSnapshot $snapshot): NormalizedProductData
    {
        $raw = json_decode($snapshot->rawData, true);

        if (! is_array($raw)) {
            // Treat as HTML
            $raw = $this->scraper()->extract(
                $snapshot->rawData,
                $this->config['selectors'] ?? []
            );
        }

        // Support both the legacy JSON shape used by tests/fixtures
        // (product.title, price.amount, seller.name, …) and the flat
        // shape produced by the scraper (title, price, seller, …).
        $title = $raw['title']
            ?? $raw['product']['title']
            ?? null;

        $priceData = $raw['price'] ?? $raw['product']['price'] ?? [];
        $seller = $raw['seller']
            ?? (isset($raw['product']['seller']) ? $raw['product']['seller'] : null);

        return NormalizedProductData::create([
            'provider' => $snapshot->provider,
            'externalId' => $snapshot->externalId,
            'title' => $title,
            'price' => [
                'amount' => $priceData['amount'] ?? 0,
                'currency' => $priceData['currency'] ?? 'IRR',
                'display' => $priceData['display'] ?? null,
            ],
            'availability' => $raw['availability'] ?? null,
            'stockStatus' => $raw['stock_status'] ?? null,
            'seller' => is_array($seller) ? $seller : (isset($seller) ? ['name' => $seller] : null),
            'badges' => $raw['badges'] ?? [],
            'imageUrl' => $raw['image'] ?? $raw['image_url'] ?? null,
            'productUrl' => $raw['product_url'] ?? null,
            'observedAt' => $raw['posted_at'] ?? $raw['observed_at'] ?? null,
            'raw' => $raw,
        ]);
    }

    public function getProviderName(): string
    {
        return 'torob';
    }

    protected function buildUrl(ProductReference $reference): string
    {
        $base = $this->config['base_url'] ?? 'https://torob.com';

        return $base.'/p/'.$reference->externalId.'/'.$reference->canonicalUrl.'/';
    }
}
