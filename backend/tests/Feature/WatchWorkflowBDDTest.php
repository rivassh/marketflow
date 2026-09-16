<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Watch;
use App\Services\WorkflowDispatcher;
use App\Providers\Processors\TorobProcessor;
use App\Providers\DTOs\{ProductReference};
use App\Workflow\WorkflowConditionEvaluator;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * BDD Feature Tests for Watch Workflow
 *
 * These tests describe the expected behavior of the price-watch workflow
 * using BDD-style naming: "given/when/then" pattern via test method names.
 */
class WatchWorkflowBDDTest extends TestCase
{
    use RefreshDatabase;

    protected WorkflowDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new WorkflowDispatcher();
    }

    /**
     * Given a user has a price-below watch
     * When a new watch is created
     * Then it should be persisted with the alert conditions
     */
    public function test_a_new_watch_is_created_with_price_alert_conditions(): void
    {
        $watch = Watch::factory()->create([
            'user_id' => 1,
            'product_url' => 'https://torob.com/p/12345/iphone-14-pro/',
            'workflow_type' => 'PRICE_BELOW_ALERT',
            'conditions' => [
                [
                    'operator' => 'less_than',
                    'value' => 'price.amount',
                    'expected_value' => 10000000,
                ]
            ],
            'frequency_minutes' => 15,
            'is_active' => true,
            'is_tracked' => true,
        ]);

        $this->assertDatabaseHas('watches', [
            'id' => $watch->id,
            'product_url' => 'https://torob.com/p/12345/iphone-14-pro/',
        ]);
    }

    /**
     * Given a watch with price-below condition
     * When the product price drops below the threshold
     * Then the alert should be triggered and an observation recorded
     */
    public function test_alert_is_triggered_when_price_is_below_threshold(): void
    {
        Http::fake([
            'torob.com/*' => Http::response([
                'product' => [
                    'title' => 'iPhone 14 Pro',
                    'brand' => 'Apple',
                ],
                'price' => [
                    'amount' => 8500000,
                    'currency' => 'IRR',
                ],
                'availability' => 'available',
            ], 200),
        ]);

        $processor = new TorobProcessor();
        $reference = ProductReference::create('torob', '12345', 'iphone-14-pro');
        $snapshot = $processor->fetchProduct($reference);
        $normalized = $processor->normalizeSnapshot($snapshot);

        $this->assertEquals(8500000, $normalized->price['amount']);

        $conditions = [[
            'operator' => 'less_than',
            'value' => 'price.amount',
            'expected_value' => 10000000,
        ]];

        $evaluator = new WorkflowConditionEvaluator();
        $result = $evaluator->evaluate($normalized, $conditions);

        $this->assertTrue($result, 'Price below threshold should trigger alert');
    }

    /**
     * Given a watch with price-below condition
     * When the product price is above the threshold
     * Then the alert should NOT be triggered
     */
    public function test_alert_is_not_triggered_when_price_is_above_threshold(): void
    {
        Http::fake([
            'torob.com/*' => Http::response([
                'product' => ['title' => 'iPhone 14 Pro'],
                'price' => ['amount' => 12000000, 'currency' => 'IRR'],
                'availability' => 'available',
            ], 200),
        ]);

        $processor = new TorobProcessor();
        $reference = ProductReference::create('torob', '12345', 'iphone-14-pro');
        $snapshot = $processor->fetchProduct($reference);
        $normalized = $processor->normalizeSnapshot($snapshot);

        $conditions = [[
            'operator' => 'less_than',
            'value' => 'price.amount',
            'expected_value' => 10000000,
        ]];

        $evaluator = new WorkflowConditionEvaluator();
        $result = $evaluator->evaluate($normalized, $conditions);

        $this->assertFalse($result, 'Price above threshold should NOT trigger alert');
    }

    /**
     * Given a watch with multiple conditions joined by AND
     * When all conditions are met
     * Then the alert should be triggered
     */
    public function test_multiple_conditions_with_and_logic(): void
    {
        Http::fake([
            'torob.com/*' => Http::response([
                'product' => ['title' => 'iPhone 14 Pro'],
                'price' => ['amount' => 9500000, 'currency' => 'IRR'],
                'availability' => 'available',
            ], 200),
        ]);

        $processor = new TorobProcessor();
        $reference = ProductReference::create('torob', '12345', 'iphone-14-pro');
        $snapshot = $processor->fetchProduct($reference);
        $normalized = $processor->normalizeSnapshot($snapshot);

        $conditions = [
            [
                'operator' => 'less_than',
                'value' => 'price.amount',
                'expected_value' => 10000000,
            ],
            [
                'operator' => 'equals',
                'value' => 'availability',
                'expected_value' => 'available',
            ],
        ];

        $evaluator = new WorkflowConditionEvaluator();
        $result = $evaluator->evaluate($normalized, $conditions);

        $this->assertTrue($result, 'All conditions met should trigger alert');
    }

    /**
     * Given a watch with availability check
     * When the product becomes unavailable
     * Then the alert should be triggered (out-of-stock notification)
     */
    public function test_alert_when_product_becomes_unavailable(): void
    {
        Http::fake([
            'torob.com/*' => Http::response([
                'product' => ['title' => 'iPhone 14 Pro'],
                'price' => ['amount' => 9500000, 'currency' => 'IRR'],
                'availability' => 'unavailable',
            ], 200),
        ]);

        $processor = new TorobProcessor();
        $reference = ProductReference::create('torob', '12345', 'iphone-14-pro');
        $snapshot = $processor->fetchProduct($reference);
        $normalized = $processor->normalizeSnapshot($snapshot);

        $conditions = [[
            'operator' => 'equals',
            'value' => 'availability',
            'expected_value' => 'unavailable',
        ]];

        $evaluator = new WorkflowConditionEvaluator();
        $result = $evaluator->evaluate($normalized, $conditions);

        $this->assertTrue($result, 'Unavailable product should trigger notification');
    }

    /**
     * Given a non-existent product
     * When the watch checks
     * Then the error should be handled gracefully
     */
    public function test_observations_handle_failed_fetches(): void
    {
        Http::fake([
            'torob.com/*' => Http::response(['error' => 'Not found'], 404),
        ]);

        $processor = new TorobProcessor();
        $reference = ProductReference::create('torob', '99999', 'missing-item');

        $threwException = false;
        try {
            $processor->fetchProduct($reference);
        } catch (\Exception $e) {
            $threwException = true;
        }

        $this->assertTrue($threwException, '404 response should throw an exception');
    }

    /**
     * Given a price watch with a between-range condition
     * When the price falls within the range
     * Then the alert should be triggered
     */
    public function test_alert_when_price_in_range(): void
    {
        Http::fake([
            'torob.com/*' => Http::response([
                'product' => ['title' => 'iPhone 14 Pro'],
                'price' => ['amount' => 9500000, 'currency' => 'IRR'],
                'availability' => 'available',
            ], 200),
        ]);

        $processor = new TorobProcessor();
        $reference = ProductReference::create('torob', '12345', 'iphone-14-pro');
        $snapshot = $processor->fetchProduct($reference);
        $normalized = $processor->normalizeSnapshot($snapshot);

        $conditions = [[
            'operator' => 'between',
            'value' => 'price.amount',
            'expected_value' => [9000000, 10000000],
        ]];

        $evaluator = new WorkflowConditionEvaluator();
        $result = $evaluator->evaluate($normalized, $conditions);

        $this->assertTrue($result, 'Price within range should trigger alert');
    }

    /**
     * Given a regex-based condition
     * When the title matches the pattern
     * Then the alert should be triggered
     */
    public function test_alert_with_regex_pattern(): void
    {
        Http::fake([
            'torob.com/*' => Http::response([
                'product' => ['title' => 'iPhone 14 Pro Max'],
                'price' => ['amount' => 9500000, 'currency' => 'IRR'],
                'availability' => 'available',
            ], 200),
        ]);

        $processor = new TorobProcessor();
        $reference = ProductReference::create('torob', '12345', 'iphone-14-pro');
        $snapshot = $processor->fetchProduct($reference);
        $normalized = $processor->normalizeSnapshot($snapshot);

        $conditions = [[
            'operator' => 'matches_regex',
            'value' => 'title',
            'expected_value' => '/iPhone.*Pro/i',
        ]];

        $evaluator = new WorkflowConditionEvaluator();
        $result = $evaluator->evaluate($normalized, $conditions);

        $this->assertTrue($result, 'Title matching regex should trigger alert');
    }
}
