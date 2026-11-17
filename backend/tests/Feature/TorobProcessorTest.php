<?php

namespace Tests\Feature;

use App\Providers\DTOs\ProductReference;
use App\Providers\DTOs\ProviderProductSnapshot;
use App\Providers\Processors\TorobProcessor;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TorobProcessorTest extends TestCase
{
    protected TorobProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new TorobProcessor;
    }

    public function test_it_supports_torob_urls(): void
    {
        $this->assertTrue($this->processor->supports('https://torob.com/p/12345/iphone-14-pro/'));
        $this->assertTrue($this->processor->supports('http://torob.com/p/67890/iphone-14/'));
    }

    public function test_it_does_not_support_non_torob_urls(): void
    {
        // These URLs should NOT be supported by torob processor
        $this->assertFalse($this->processor->supports('https://divar.ir/12345'));
        $this->assertFalse($this->processor->supports('https://example.com/product'));
        $this->assertFalse($this->processor->supports('https://other-market.com/p/12345'));
    }

    public function test_it_parses_torob_url_correctly(): void
    {
        $url = 'https://torob.com/p/12345/iphone-14-pro-max-review/';
        $reference = $this->processor->parseProductReference($url);

        $this->assertInstanceOf(ProductReference::class, $reference);
        $this->assertEquals('torob', $reference->provider);
        $this->assertEquals('12345', $reference->externalId);
    }

    public function test_it_fetches_and_normalizes_product_data(): void
    {
        $reference = ProductReference::create('torob', '12345', 'iphone-14-pro');

        Http::fake([
            'torob.com/*' => Http::response([
                'product' => [
                    'title' => 'آیفون ۱۴ پرو',
                    'brand' => 'Apple',
                    'model' => 'iPhone 14 Pro',
                ],
                'price' => [
                    'amount' => 9500000,
                    'currency' => 'IRR',
                    'display' => '۹.۵ میلیون تومان',
                ],
                'availability' => 'available',
                'stock_status' => 'موجود',
                'seller' => [
                    'name' => 'فروشگاه تکنولوژی',
                    'url' => 'https://torob.com/seller/123',
                ],
            ], 200),
        ]);

        $snapshot = $this->processor->fetchProduct($reference);
        $this->assertInstanceOf(ProviderProductSnapshot::class, $snapshot);

        $normalized = $this->processor->normalizeSnapshot($snapshot);

        $this->assertEquals('torob', $normalized->provider);
        $this->assertEquals('12345', $normalized->externalId);
        $this->assertEquals('آیفون ۱۴ پرو', $normalized->title);
        $this->assertEquals(9500000, $normalized->price['amount']);
        $this->assertEquals('IRR', $normalized->price['currency']);
        $this->assertEquals('available', $normalized->availability);
    }

    public function test_it_handles_unavailable_products(): void
    {
        $reference = ProductReference::create('torob', '67890', 'out-of-stock-item');

        Http::fake([
            'torob.com/*' => Http::response([
                'product' => [
                    'title' => 'محصول ناموجود',
                ],
                'price' => [
                    'amount' => 0,
                    'currency' => 'IRR',
                ],
                'availability' => 'unavailable',
                'stock_status' => 'ناموجود',
            ], 200),
        ]);

        $normalized = $this->processor->normalizeSnapshot(
            $this->processor->fetchProduct($reference)
        );

        $this->assertEquals('unavailable', $normalized->availability);
        $this->assertEquals('ناموجود', $normalized->stockStatus);
    }

    // --- Closed-box: UUID id + Persian slug ---

    public function test_it_supports_uuid_and_persian_slug_urls(): void
    {
        $url = 'https://torob.com/p/49216e75-b6f3-4266-985d-8b034b8a9716/هدفون-بلوتوثی-موزیک-اپولو-مدل-yrb-22/';

        $this->assertTrue($this->processor->supports($url));
    }

    public function test_it_parses_uuid_reference(): void
    {
        $url = 'https://torob.com/p/49216e75-b6f3-4266-985d-8b034b8a9716/هدفون-بلوتوثی-موزیک-اپولو-مدل-yrb-22/';

        $reference = $this->processor->parseProductReference($url);

        $this->assertInstanceOf(ProductReference::class, $reference);
        $this->assertEquals('torob', $reference->provider);
        $this->assertEquals('49216e75-b6f3-4266-985d-8b034b8a9716', $reference->externalId);
        $this->assertEquals('هدفون-بلوتوثی-موزیک-اپولو-مدل-yrb-22', $reference->canonicalUrl);
    }

    public function test_it_parses_uppercase_uuid_and_url_encoded_slug(): void
    {
        $url = 'https://torob.com/p/49216E75-B6F3-4266-985D-8B034B8A9716/%D9%87%D8%AF%D9%81%D9%88%D9%86-%D8%A8%D9%84%D9%88%D8%AA%D9%88%D8%AB%DB%8C/';

        $reference = $this->processor->parseProductReference($url);

        $this->assertEquals('49216e75-b6f3-4266-985d-8b034b8a9716', $reference->externalId);
        $this->assertEquals('هدفون-بلوتوثی', $reference->canonicalUrl);
    }

    public function test_it_normalizes_scraped_persian_digit_price(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/torob_uuid_product.html');
        $this->assertNotFalse($html, 'fixture must exist');

        $snapshot = ProviderProductSnapshot::create(
            'torob',
            '49216e75-b6f3-4266-985d-8b034b8a9716',
            $html
        );

        $normalized = $this->processor->normalizeSnapshot($snapshot);

        $this->assertEquals(4400000, $normalized->price['amount']);
        $this->assertStringContainsString('۴٫۴۰۰٫۰۰۰', $normalized->price['display'] ?? '');
    }

    public function test_it_normalizes_arabic_digit_price_string(): void
    {
        $snapshot = ProviderProductSnapshot::create(
            'torob',
            '12345',
            json_encode([
                'title' => 'Test',
                'price' => '٤٤٠٠٠٠٠',
            ])
        );

        $normalized = $this->processor->normalizeSnapshot($snapshot);

        $this->assertEquals(4400000, $normalized->price['amount']);
    }

    public function test_it_keeps_legacy_json_price_shape(): void
    {
        $snapshot = ProviderProductSnapshot::create(
            'torob',
            '12345',
            json_encode([
                'product' => [
                    'title' => 'آیفون',
                    'price' => [
                        'amount' => 9500000,
                        'currency' => 'IRR',
                        'display' => '۹.۵ میلیون تومان',
                    ],
                ],
            ])
        );

        $normalized = $this->processor->normalizeSnapshot($snapshot);

        $this->assertEquals(9500000, $normalized->price['amount']);
        $this->assertSame('IRR', $normalized->price['currency']);
        $this->assertStringContainsString('میلیون', $normalized->price['display'] ?? '');
    }

    public function test_it_serves_fixture_path_for_deterministic_checks(): void
    {
        $fixture = __DIR__.'/../Fixtures/torob_uuid_product.html';
        $this->assertFileExists($fixture);

        $processor = new TorobProcessor(
            null,
            ['fixture_path' => $fixture, 'base_url' => 'https://torob.com']
        );

        $reference = ProductReference::create(
            'torob',
            '49216e75-b6f3-4266-985d-8b034b8a9716',
            'هدفون-بلوتوثی-موزیک-اپولو-مدل-yrb-22'
        );

        $snapshot = $processor->fetchProduct($reference);
        $this->assertSame(
            '49216e75-b6f3-4266-985d-8b034b8a9716',
            $snapshot->externalId
        );

        $normalized = $processor->normalizeSnapshot($snapshot);
        $this->assertEquals(4400000, $normalized->price['amount']);
        $this->assertStringContainsString('۴٫۴۰۰٫۰۰۰', $normalized->price['display'] ?? '');
    }
}
