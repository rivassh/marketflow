<?php

namespace Tests\Unit;

use App\Providers\DTOs\NormalizedProductData;
use App\Providers\DTOs\ProductReference;
use App\Providers\DTOs\ProviderProductSnapshot;
use PHPUnit\Framework\TestCase;

class ProductDTOTest extends TestCase
{
    public function test_normalized_product_data_can_be_created_with_array(): void
    {
        $data = NormalizedProductData::create([
            'provider' => 'torob',
            'externalId' => '12345',
            'title' => 'آیفون 14 پرو',
            'price' => ['amount' => 9500000, 'currency' => 'IRR'],
            'availability' => 'available',
        ]);

        $this->assertInstanceOf(NormalizedProductData::class, $data);
        $this->assertEquals('torob', $data->provider);
        $this->assertEquals('12345', $data->externalId);
        $this->assertEquals('آیفون 14 پرو', $data->title);
        $this->assertEquals(9500000, $data->price['amount']);
        $this->assertEquals('IRR', $data->price['currency']);
        $this->assertEquals('available', $data->availability);
    }

    public function test_normalized_product_data_allows_null_values(): void
    {
        $data = NormalizedProductData::create([]);

        $this->assertNull($data->provider);
        $this->assertNull($data->title);
        $this->assertNull($data->price);
        $this->assertNull($data->availability);
    }

    public function test_product_reference_can_be_created(): void
    {
        $ref = ProductReference::create('torob', '12345', 'iphone-14-pro');

        $this->assertInstanceOf(ProductReference::class, $ref);
        $this->assertEquals('torob', $ref->provider);
        $this->assertEquals('12345', $ref->externalId);
        $this->assertEquals('iphone-14-pro', $ref->canonicalUrl);
    }

    public function test_product_reference_is_immutable(): void
    {
        $ref = ProductReference::create('torob', '12345', 'canonical');

        $this->assertEquals('torob', $ref->provider);
    }

    public function test_provider_product_snapshot_includes_content_hash(): void
    {
        $snapshot = ProviderProductSnapshot::create('torob', '12345', '{"raw": "data"}');

        $this->assertInstanceOf(ProviderProductSnapshot::class, $snapshot);
        $this->assertEquals('torob', $snapshot->provider);
        $this->assertEquals('12345', $snapshot->externalId);
        $this->assertEquals('{"raw": "data"}', $snapshot->rawData);
        $this->assertEquals(md5('{"raw": "data"}'), $snapshot->contentHash);
    }

    public function test_provider_product_snapshot_content_hash_is_md5(): void
    {
        $rawData = json_encode(['product' => ['title' => 'Test']]);
        $snapshot = ProviderProductSnapshot::create('torob', '12345', $rawData);

        $this->assertEquals(md5($rawData), $snapshot->contentHash);
    }

    public function test_normalized_product_data_price_amount_is_integer(): void
    {
        $data = NormalizedProductData::create([
            'price' => ['amount' => 9500000],
        ]);

        $this->assertIsInt($data->price['amount']);
        $this->assertEquals(9500000, $data->price['amount']);
    }
}
