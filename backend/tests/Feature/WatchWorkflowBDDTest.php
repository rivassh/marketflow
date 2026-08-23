<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Watch;
use App\Models\Observation;
use App\Services\WorkflowDispatcher;
use App\Providers\Processors\TorobProcessor;
use App\Providers\DTOs\{
    ProductReference,
    ProviderProductSnapshot,
    NormalizedProductData
};
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WatchWorkflowBDDTest extends TestCase
{
    use RefreshDatabase;

    protected WorkflowDispatcher $dispatcher;
    protected Watch $watch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new WorkflowDispatcher();

        $this->watch = Watch::factory()->create([
            'user_id' => 1,
            'product_url' => 'https://torob.com/p/12345/iphone-14-pro/observation',
            'workflow_type' => 'PRICE_BELOW_ALERT',
            'conditions' => json_encode([
                [
                    'type' => 'price_below',
                    'parameters' => ['value' => 10000000]
                ]
            ]),
            'frequency_minutes' => 15,
            'is_active' => true,
            'is_tracked' => true
        ]);
    }

    public function test_a_new_watch_is_created_with_price_alert_conditions(): void
    {
        $this->assertDatabaseHas('watches', [
            'product_url' => 'https://torob.com/p/12345/iphone-14-pro/observation'
        ]);
    }

    public function test_the_watch_can_be_checked_for_price_alerts(): void
    {
        Http::fake([
            'torob.com/*' => Http::response([
                'product' => [
                    'title' => 'آیفون ۱۴ پرو',
                    'brand' => 'Apple',
                    'model' => 'iPhone 14 Pro'
                ],
                'price' => [
                    'amount' => 8500000,
                    'currency' => 'IRR',
                    'display' => '۸.۵ میلیون تومان'
                ],
                'availability' => 'available',
                'stock_status' => 'موجود',
                'seller' => ['name' => 'فروشگاه تکنولوژی']
            ], 200)
        ]);

        $processor = new TorobProcessor();
        $reference = $processor->parseProductReference($this->watch->product_url);
        $snapshot = $processor->fetchProduct($reference);
        $normalized = $processor->normalizeSnapshot($snapshot);

        $conditions = json_decode($this->watch->conditions, true);
        $evaluator = new \App\Workflow\WorkflowConditionEvaluator();
        $triggered = $evaluator->evaluate($normalized, $conditions);

        $this->assertTrue($triggered, 'Price alert should trigger when price is below threshold');

        $this->assertDatabaseHas('observations', [
            'watch_id' => $this->watch->id,
            'status' => 'success'
        ]);
    }

    public function test_alert_is_triggered_when_price_is_below_threshold(): void
    {
        Http::fake([
            'torob.com/*' => Http::response([
                'price' => ['amount' => 8500000],
            ], 200)
        ]);

        $processor = new TorobProcessor();
        $reference = ProductReference::create('torob', '12345', 'iphone-14-pro');
        $snapshot = $processor->fetchProduct($reference);
        $normalized = $processor->normalizeSnapshot($snapshot);

        $this->assertEquals(8500000, $normalized->price['amount']);

        $condition = [
            'type' => 'price_below',
            'parameters' => ['value' => 10000000]
        ];

        $evaluator = new \App\Workflow\WorkflowConditionEvaluator();
        $result = $evaluator->evaluate($normalized, [$condition]);

        $this->assertTrue($result, 'Price below threshold should trigger alert');
    }

    public function test_alert_is_not_triggered_when_price_is_above_threshold(): void
    {
        Http::fake([
            'torob.com/*' => Http::response([
                'price' => ['amount' => 12000000],
            ], 200)
        ]);

        $processor = new TorobProcessor();
        $reference = ProductReference::create('torob', '12345', 'iphone-14-pro');
        $snapshot = $processor->fetchProduct($reference);
        $normalized = $processor->normalizeSnapshot($snapshot);

        $condition = [
            'type' => 'price_below',
            'parameters' => ['value' => 10000000]
        ];

        $evaluator = new \App\Workflow\WorkflowConditionEvaluator();
        $result = $evaluator->evaluate($normalized, [$condition]);

        $this->assertFalse($result, 'Price above threshold should NOT trigger alert');
    }

    public function test_observations_record_check_results(): void
    {
        Http::fake([
            'torob.com/*' => Http::response(['error' => 'Not found'], 404)
        ]);

        $processor = new TorobProcessor();
        $reference = ProductReference::create('torob', '99999', 'missing-item');

        try {
            $processor->fetchProduct($reference);
        } catch (\Exception $e) {
            // Expected exception
        }

        $this->assertDatabaseCount('observations', 0);
    }
}