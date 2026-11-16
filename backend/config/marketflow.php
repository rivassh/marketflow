<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Marketplace Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Per-provider settings: endpoints, selectors, scraping flags,
    | rate limits, and robots compliance.
    |
    */

    'torob' => [
        'enabled' => env('TOROB_ENABLED', true),
        'base_url' => 'https://torob.com',
        'api_timeout' => 10,
        'retry_count' => 2,
        'cache_ttl' => 300,
        'user_agent' => 'MarketFlow/3.0 (+https://marketflow.example)',
        // When true the processor requests HTML and parses it with the
        // selectors below. When false it expects a JSON endpoint.
        'scrape_html' => env('TOROB_SCRAPE_HTML', true),
        'accept' => 'text/html',
        'selectors' => [
            'title' => 'h1.product-title, meta[property="og:title"]@content',
            'price' => '.price-amount, meta[property="product:price:amount"]@content',
            'currency' => '.price-currency, meta[property="product:price:currency"]@content',
            'availability' => '.stock-status, meta[property="product:availability"]@content',
            'stock_status' => '.stock-label',
            'seller' => '.seller-name, meta[property="product:seller"]@content',
            'seller_url' => '.seller-link@href, meta[property="product:seller:url"]@content',
            'image' => 'meta[property="og:image"]@content, .product-image img@src',
            'posted_at' => '.publish-date, meta[property="article:published_time"]@content',
        ],
        'rate_limit' => [
            'requests_per_minute' => env('TOROB_RATE_LIMIT', 10),
            'delay_between_requests_ms' => env('TOROB_DELAY_MS', 1000),
        ],
        'respect_robots' => env('TOROB_RESPECT_ROBOTS', true),
    ],

    'divar' => [
        'enabled' => env('DIVAR_ENABLED', true),
        'base_url' => 'https://divar.ir',
        'api_timeout' => 10,
        'retry_count' => 2,
        'cache_ttl' => 300,
        'user_agent' => 'MarketFlow/3.0 (+https://marketflow.example)',
        // Divar has no public API. Scraping is opt-in and off by default.
        'scrape_html' => env('DIVAR_SCRAPE_HTML', false),
        'accept' => 'text/html',
        'selectors' => [
            'title' => 'h1, meta[property="og:title"]@content',
            'price' => '.price-amount, meta[property="product:price:amount"]@content',
            'city' => '.city, meta[property="product:city"]@content',
            'category' => '.category, meta[property="product:category"]@content',
            'seller' => '.seller-name, meta[property="product:seller"]@content',
            'posted_at' => '.post-date, meta[property="article:published_time"]@content',
            'image' => 'meta[property="og:image"]@content, .gallery img@src',
        ],
        'rate_limit' => [
            'requests_per_minute' => env('DIVAR_RATE_LIMIT', 5),
            'delay_between_requests_ms' => env('DIVAR_DELAY_MS', 2000),
        ],
        'respect_robots' => env('DIVAR_RESPECT_ROBOTS', true),
    ],

    'defaults' => [
        'api_timeout' => 10,
        'retry_count' => 2,
        'cache_ttl' => 300,
        'user_agent' => 'MarketFlow/3.0 (+https://marketflow.example)',
    ],
];
