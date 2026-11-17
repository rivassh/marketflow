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

    /**
     * Torob product URLs look like:
     *   https://torob.com/p/{id}/{slug}/
     * where {id} is either a numeric id (legacy) or a UUID, and {slug}
     * may contain Persian/Arabic letters, digits, ASCII letters, and
     * hyphens (optionally URL-encoded).
     */
    protected function productUrlPattern(): string
    {
        return '#^https?://torob\.com/p/'
            .'([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}|\d+)'
            .'/([a-z0-9\p{L}\p{N}%-]+)'
            .'#iu';
    }

    public function supports($url): bool
    {
        return (bool) preg_match(
            $this->productUrlPattern(),
            (string) $url
        );
    }

    public function parseProductReference($url): ProductReference
    {
        preg_match(
            $this->productUrlPattern(),
            (string) $url,
            $matches
        );

        $externalId = isset($matches[1]) ? strtolower($matches[1]) : '';
        // Decode any URL-encoded slug so buildUrl() can re-emit readable UTF-8.
        $slug = isset($matches[2]) ? rawurldecode($matches[2]) : '';

        return ProductReference::create(
            'torob',
            $externalId,
            $slug
        );
    }

    public function fetchProduct(ProductReference $reference): ProviderProductSnapshot
    {
        $url = $this->buildUrl($reference);

        // Test-only seam: when a fixture path is configured, serve that file
        // instead of hitting the network. This lets closed-box tests assert
        // deterministic prices (e.g. Persian digits) without mocking Http::fake.
        $fixture = $this->config['fixture_path'] ?? null;

        if (is_string($fixture) && is_file($fixture)) {
            $html = file_get_contents($fixture);

            return ProviderProductSnapshot::create(
                $reference->provider,
                $reference->externalId,
                $html
            );
        }

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

    /**
     * Normalize Persian/Arabic digits and separators to ASCII digits.
     */
    protected function normalizeDigits(string $value): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $ascii = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace(
            array_merge($persian, $arabic),
            array_merge($ascii, $ascii),
            $value
        );
    }

    /**
     * Parse a price value (int, float, or string with Persian/Arabic digits
     * and separators) into an integer minor-unit amount. Returns null when
     * no numeric value can be extracted.
     */
    protected function parsePriceAmount($value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = $this->normalizeDigits($value);
        $digits = preg_replace('/\D/', '', $normalized);

        if ($digits === '') {
            return null;
        }

        return (int) $digits;
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

        // The scraper returns flat scalar values (e.g. price => "۴٫۴۰۰٫۰۰۰").
        // When price is a scalar string, normalize Persian/Arabic digits to an
        // integer amount and keep the original display string.
        if (is_string($priceData) || is_numeric($priceData)) {
            $display = is_string($priceData) ? $priceData : null;
            $amount = $this->parsePriceAmount($priceData);

            $price = [
                'amount' => $amount ?? 0,
                'currency' => $raw['currency'] ?? 'IRR',
                'display' => $display,
            ];
        } else {
            $price = [
                'amount' => $priceData['amount'] ?? 0,
                'currency' => $priceData['currency'] ?? 'IRR',
                'display' => $priceData['display'] ?? null,
            ];
        }

        return NormalizedProductData::create([
            'provider' => $snapshot->provider,
            'externalId' => $snapshot->externalId,
            'title' => $title,
            'price' => $price,
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
