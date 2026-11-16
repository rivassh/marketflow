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
}
