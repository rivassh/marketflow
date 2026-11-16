<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;

/**
 * Shared HTML scraping helper used by TorobProcessor and DivarProcessor.
 *
 * Selectors are configured in config/marketflow.php under each provider's
 * `selectors` map. Each selector is a comma-separated CSS path with an
 * optional `@attr` suffix to extract an attribute value.
 *
 * Example: 'h1.product-title, meta[property="og:title"]@content'
 */
class ScrapingHelper
{
    /**
     * Fetch raw HTML from a URL with retry and rate-limit awareness.
     */
    public function fetchHtml(
        string $url,
        string $userAgent,
        string $accept = 'text/html',
        int $timeout = 10,
        int $retries = 2
    ): string {
        $attempt = 0;
        $lastError = null;

        while ($attempt <= $retries) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $userAgent,
                    'Accept' => $accept,
                ])->timeout($timeout)
                    ->withOptions(['http_errors' => false])
                    ->get($url);

                if ($response->successful()) {
                    return $response->body();
                }

                $lastError = "HTTP {$response->status()}";
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }

            $attempt++;
            if ($attempt <= $retries) {
                sleep(1);
            }
        }

        throw new \Exception("Scrape failed for {$url}: {$lastError}");
    }

    /**
     * Extract values from HTML using configured selectors.
     *
     * @param  array<string, string>  $selectors  key => css-selector[@attr]
     * @return array<string, mixed>
     */
    public function extract(string $html, array $selectors): array
    {
        $result = [];

        foreach ($selectors as $key => $selectorString) {
            $values = [];
            foreach (explode(',', $selectorString) as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                $v = $this->extractSelector($html, $part);
                if ($v !== []) {
                    $values = $v;
                    break;
                }
            }
            if ($values !== []) {
                $result[$key] = $values[0];
            } else {
                $result[$key] = null;
            }
        }

        return $result;
    }

    /**
     * Extract the first matching value for a single selector.
     */
    protected function extractSelector(string $html, string $selectorString): array
    {
        $parts = explode('@', $selectorString, 2);
        $css = trim($parts[0]);
        $attr = $parts[1] ?? null;

        // Use regex-based extraction as a lightweight fallback when the
        // full DOM crawler is not installed. This is intentionally simple
        // and only handles meta tags and simple class/ID selectors.
        if (str_starts_with($css, 'meta[')) {
            return $this->extractMeta($html, $css, $attr);
        }

        if (preg_match('/^\.([a-zA-Z0-9_-]+)/', $css, $m)) {
            return $this->extractByClass($html, $m[1], $attr);
        }

        if (preg_match('/^#([a-zA-Z0-9_-]+)/', $css, $m)) {
            return $this->extractById($html, $m[1], $attr);
        }

        if (preg_match('/^h1/', $css)) {
            return $this->extractTagContent($html, 'h1');
        }

        return [];
    }

    protected function extractMeta(string $html, string $css, ?string $attr): array
    {
        $pattern = '/<meta[^>]+>/i';
        preg_match_all($pattern, $html, $matches);

        $results = [];
        foreach ($matches[0] as $tag) {
            if (preg_match('/property\s*=\s*["\']([^"\']+)["\']/', $tag, $prop)
                && str_contains($css, $prop[1])) {
                if ($attr && preg_match('/'.preg_quote($attr, '/').'\s*=\s*["\']([^"\']*)["\']/', $tag, $av)) {
                    $results[] = $av[1];
                }
            }
        }

        return $results;
    }

    protected function extractByClass(string $html, string $className, ?string $attr): array
    {
        $pattern = '/<([a-z0-9]+)[^>]*class="[^"]*\\b'.preg_quote($className, '/').'\\b[^"]*"[^>]*>(.*?)<\\/\\1>/is';
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        $results = [];
        foreach ($matches as $m) {
            $tag = $m[0];
            if ($attr) {
                if (preg_match('/'.preg_quote($attr, '/').'\s*=\s*["\']([^"\']*)["\']/', $tag, $av)) {
                    $results[] = $av[1];
                }
            } else {
                $results[] = trim(strip_tags($m[2]));
            }
        }

        return $results;
    }

    protected function extractById(string $html, string $id, ?string $attr): array
    {
        $pattern = '/<[^>]+id="[^"]*\b'.preg_quote($id, '/').'\b[^"]*"[^>]*>(.*?)<\/[^>]+>/is';
        preg_match($pattern, $html, $m);

        if (! isset($m[1])) {
            return [];
        }

        $content = trim($m[1]);

        return $attr ? [] : [$content];
    }

    protected function extractTagContent(string $html, string $tag): array
    {
        $pattern = '/<'.$tag.'[^>]*>(.*?)<\/'.$tag.'>/is';
        preg_match_all($pattern, $html, $matches);

        return array_map('trim', $matches[1]);
    }
}
