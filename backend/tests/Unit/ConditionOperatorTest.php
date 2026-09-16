<?php

namespace Tests\Unit;

use App\Workflow\ConditionOperator;
use PHPUnit\Framework\TestCase;

class ConditionOperatorTest extends TestCase
{
    public function test_each_enum_case_has_persian_label(): void
    {
        foreach (ConditionOperator::cases() as $case) {
            $label = $case->label();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
            // Persian labels contain non-ASCII characters
            $this->assertMatchesRegularExpression('/[^\x00-\x7F]/', $label);
        }
    }

    public function test_equals_label(): void
    {
        $this->assertEquals('برابر است', ConditionOperator::EQUALS->label());
    }

    public function test_less_than_label(): void
    {
        $this->assertEquals('کوچکتر است', ConditionOperator::LESS_THAN->label());
    }

    public function test_greater_than_label(): void
    {
        $this->assertEquals('بزرگتر است', ConditionOperator::GREATER_THAN->label());
    }

    public function test_between_label(): void
    {
        $this->assertEquals('بین', ConditionOperator::BETWEEN->label());
    }

    public function test_contains_label(): void
    {
        $this->assertEquals('شامل است', ConditionOperator::CONTAINS->label());
    }

    public function test_matches_regex_label(): void
    {
        $this->assertEquals('مطابقت regex دارد', ConditionOperator::MATCHES_REGEX->label());
    }

    public function test_not_equals_label(): void
    {
        $this->assertEquals('برابر نیست', ConditionOperator::NOT_EQUALS->label());
    }

    public function test_all_operators_are_covered(): void
    {
        $expectedOperators = [
            'EQUALS', 'NOT_EQUALS', 'GREATER_THAN', 'GREATER_THAN_OR_EQUAL',
            'LESS_THAN', 'LESS_THAN_OR_EQUAL', 'BETWEEN', 'CONTAINS',
            'NOT_CONTAINS', 'STARTS_WITH', 'ENDS_WITH', 'EXISTS',
            'NOT_EXISTS', 'CHANGED', 'CHANGED_TO', 'CHANGED_FROM', 'MATCHES_REGEX'
        ];
        $actualOperators = array_map(fn($c) => $c->name, ConditionOperator::cases());
        sort($expectedOperators);
        sort($actualOperators);
        $this->assertEquals($expectedOperators, $actualOperators);
    }
}
