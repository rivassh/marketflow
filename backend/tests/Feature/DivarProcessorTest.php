<?php

namespace Tests\Feature;

use App\Providers\DTOs\ProductReference;
use App\Providers\DTOs\ProviderProductSnapshot;
use App\Providers\Processors\DivarProcessor;
use App\Providers\ScrapingHelper;
use Tests\TestCase;

class DivarProcessorTest extends TestCase
{
    protected DivarProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = app(DivarProcessor::class);
    }

    public function test_it_supports_divar_urls(): void
    {
        $this->assertTrue($this->processor->supports('https://divar.ir/v/some-slug/abc123'));
        $this->assertTrue($this->processor->supports('http://divar.ir/v/another-slug/def456'));
    }

    public function test_it_does_not_support_non_divar_urls(): void
    {
        $this->assertFalse($this->processor->supports('https://torob.com/p/12345/iphone-14-pro/'));
        $this->assertFalse($this->processor->supports('https://example.com/product'));
        $this->assertFalse($this->processor->supports('https://divar.ir/12345'));
    }

    public function test_it_parses_divar_url_correctly(): void
    {
        $url = 'https://divar.ir/v/iphone-14-pro/abc123def456';
        $reference = $this->processor->parseProductReference($url);

        $this->assertInstanceOf(ProductReference::class, $reference);
        $this->assertEquals('divar', $reference->provider);
        $this->assertEquals('abc123def456', $reference->externalId);
        $this->assertEquals('iphone-14-pro', $reference->canonicalUrl);
    }

    public function test_it_normalizes_html_scrape_data(): void
    {
        $html = <<<'HTML'
        <html>
            <head>
                <meta property="og:title" content="آیفون ۱۴ پرو">
            </head>
            <body>
                <span class="price-amount">۹.۵ میلیون تومان</span>
                <span class="city">تهران</span>
                <span class="category">موبایل</span>
                <span class="seller-name">فروشگاه تکنولوژی</span>
            </body>
        </html>
        HTML;

        $snapshot = ProviderProductSnapshot::create('divar', 'abc123', $html);
        $normalized = $this->processor->normalizeSnapshot($snapshot);

        $this->assertEquals('divar', $normalized->provider);
        $this->assertEquals('abc123', $normalized->externalId);
        $this->assertEquals('تهران', $normalized->city);
        $this->assertEquals('موبایل', $normalized->category);
    }

    public function test_it_throws_when_scrape_is_disabled(): void
    {
        $processor = new DivarProcessor(
            app(ScrapingHelper::class),
            ['scrape_html' => false, 'base_url' => 'https://divar.ir']
        );

        $reference = ProductReference::create('divar', 'abc123', 'iphone-14-pro');

        $this->expectException(\Exception::class);
        $processor->fetchProduct($reference);
    }
}
