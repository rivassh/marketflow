<?php

namespace Tests\Unit;

use App\Providers\DTOs\NormalizedProductData;
use App\Workflow\ConditionEvaluatorRegistry;
use App\Workflow\ConditionOperator;
use App\Workflow\WorkflowConditionEvaluator;
use PHPUnit\Framework\TestCase;

class WorkflowConditionEvaluatorTest extends TestCase
{
    protected WorkflowConditionEvaluator $evaluator;

    protected NormalizedProductData $data;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new WorkflowConditionEvaluator;
        $this->data = NormalizedProductData::create([
            'provider' => 'torob',
            'externalId' => '12345',
            'title' => 'iPhone 14 Pro',
            'price' => ['amount' => 9500000, 'currency' => 'IRR', 'display' => '۹.۵ میلیون تومان'],
            'availability' => 'available',
            'stockStatus' => 'موجود',
            'seller' => ['name' => 'فروشگاه تکنولوژی'],
            'badges' => [],
            'imageUrl' => 'https://torob.com/image.jpg',
            'productUrl' => 'https://torob.com/p/12345/iphone-14-pro/',
            'observedAt' => '2025-01-01T00:00:00Z',
            'raw' => [],
        ]);
    }

    public function test_equals_operator_returns_true_when_values_match(): void
    {
        $conditions = [['operator' => 'equals', 'value' => 'availability', 'expected_value' => 'available']];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_equals_operator_returns_false_when_values_do_not_match(): void
    {
        $conditions = [['operator' => 'equals', 'value' => 'availability', 'expected_value' => 'unavailable']];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertFalse($result);
    }

    public function test_less_than_operator_returns_true_when_value_is_below_threshold(): void
    {
        $conditions = [['operator' => 'less_than', 'value' => 'price.amount', 'expected_value' => 10000000]];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_less_than_operator_returns_false_when_value_equals_threshold(): void
    {
        $conditions = [['operator' => 'less_than', 'value' => 'price.amount', 'expected_value' => 9500000]];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertFalse($result);
    }

    public function test_greater_than_operator_returns_true_when_value_is_above_threshold(): void
    {
        $conditions = [['operator' => 'greater_than', 'value' => 'price.amount', 'expected_value' => 9000000]];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_between_operator_returns_true_when_value_is_within_range(): void
    {
        $conditions = [['operator' => 'between', 'value' => 'price.amount', 'expected_value' => [9000000, 10000000]]];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_between_operator_returns_false_when_value_is_outside_range(): void
    {
        $conditions = [['operator' => 'between', 'value' => 'price.amount', 'expected_value' => [10000000, 11000000]]];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertFalse($result);
    }

    public function test_contains_operator_returns_true_when_string_contains_substring(): void
    {
        $conditions = [['operator' => 'contains', 'value' => 'title', 'expected_value' => 'iPhone']];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_contains_operator_returns_false_when_string_does_not_contain_substring(): void
    {
        $conditions = [['operator' => 'contains', 'value' => 'title', 'expected_value' => 'Samsung']];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertFalse($result);
    }

    public function test_matches_regex_operator_returns_true_when_pattern_matches(): void
    {
        $conditions = [['operator' => 'matches_regex', 'value' => 'title', 'expected_value' => '/iPhone.*Pro/i']];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_matches_regex_operator_returns_false_when_pattern_does_not_match(): void
    {
        $conditions = [['operator' => 'matches_regex', 'value' => 'title', 'expected_value' => '/Samsung/i']];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertFalse($result);
    }

    public function test_empty_conditions_returns_true(): void
    {
        $result = $this->evaluator->evaluate($this->data, []);
        $this->assertTrue($result);
    }

    public function test_single_condition_returns_result_of_that_condition(): void
    {
        $conditions = [['operator' => 'equals', 'value' => 'availability', 'expected_value' => 'available']];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_and_logic_with_multiple_conditions(): void
    {
        $conditions = [
            ['operator' => 'less_than', 'value' => 'price.amount', 'expected_value' => 10000000],
            ['operator' => 'equals', 'value' => 'availability', 'expected_value' => 'available'],
        ];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_or_logic_with_multiple_conditions(): void
    {
        $conditions = [
            ['operator' => 'less_than', 'value' => 'price.amount', 'expected_value' => 5000000],
            ['operator' => 'OR', 'value' => 'availability', 'expected_value' => 'available'],
            ['operator' => 'equals', 'value' => 'availability', 'expected_value' => 'available'],
        ];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_get_value_returns_null_for_nonexistent_path(): void
    {
        $registry = new ConditionEvaluatorRegistry;
        $result = $registry->evaluate(ConditionOperator::EQUALS, $this->data, 'nonexistent.path', 'test');
        $this->assertFalse($result);
    }

    public function test_not_equals_operator_returns_true_when_values_differ(): void
    {
        $conditions = [['operator' => 'not_equals', 'value' => 'availability', 'expected_value' => 'unavailable']];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_not_equals_operator_returns_false_when_values_match(): void
    {
        $conditions = [['operator' => 'not_equals', 'value' => 'availability', 'expected_value' => 'available']];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertFalse($result);
    }

    public function test_greater_than_or_equal_operator(): void
    {
        $conditions = [['operator' => 'greater_than_or_equal', 'value' => 'price.amount', 'expected_value' => 9500000]];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_less_than_or_equal_operator(): void
    {
        $conditions = [['operator' => 'less_than_or_equal', 'value' => 'price.amount', 'expected_value' => 9500000]];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_not_contains_operator(): void
    {
        $conditions = [['operator' => 'not_contains', 'value' => 'title', 'expected_value' => 'Samsung']];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_starts_with_operator(): void
    {
        $conditions = [['operator' => 'starts_with', 'value' => 'title', 'expected_value' => 'iPhone']];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_ends_with_operator(): void
    {
        $conditions = [['operator' => 'ends_with', 'value' => 'title', 'expected_value' => 'Pro']];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_exists_operator(): void
    {
        $conditions = [['operator' => 'exists', 'value' => 'availability', 'expected_value' => null]];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }

    public function test_not_exists_operator(): void
    {
        $conditions = [['operator' => 'not_exists', 'value' => 'nonexistent', 'expected_value' => null]];
        $result = $this->evaluator->evaluate($this->data, $conditions);
        $this->assertTrue($result);
    }
}
