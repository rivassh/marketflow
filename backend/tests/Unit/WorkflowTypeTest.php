<?php

namespace Tests\Unit;

use App\Workflow\WorkflowType;
use PHPUnit\Framework\TestCase;

class WorkflowTypeTest extends TestCase
{
    public function test_each_enum_case_has_persian_label(): void
    {
        foreach (WorkflowType::cases() as $case) {
            $label = $case->label();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
            // Persian labels contain at least one non-ASCII character
            $this->assertMatchesRegularExpression('/[^\x00-\x7F]/', $label);
        }
    }

    public function test_price_below_alert_label(): void
    {
        $this->assertEquals('هشدار قیمت کمتر از', WorkflowType::PRICE_BELOW_ALERT->label());
    }

    public function test_price_above_alert_label(): void
    {
        $this->assertEquals('هشدار قیمت بیشتر از', WorkflowType::PRICE_ABOVE_ALERT->label());
    }

    public function test_availability_alert_label(): void
    {
        $this->assertEquals('هشدار موجودی', WorkflowType::AVAILABILITY_ALERT->label());
    }

    public function test_composite_alert_label(): void
    {
        $this->assertEquals('هشدار ترکیبی', WorkflowType::COMPOSITE_ALERT->label());
    }

    public function test_price_range_alert_fields(): void
    {
        $fields = WorkflowType::PRICE_RANGE_ALERT->fields();
        $this->assertCount(2, $fields);
        $this->assertEquals('min_price', $fields[0]['name']);
        $this->assertEquals('max_price', $fields[1]['name']);
    }

    public function test_price_below_alert_fields(): void
    {
        $fields = WorkflowType::PRICE_BELOW_ALERT->fields();
        $this->assertCount(1, $fields);
        $this->assertEquals('price', $fields[0]['name']);
    }

    public function test_availability_alert_fields(): void
    {
        $fields = WorkflowType::AVAILABILITY_ALERT->fields();
        $this->assertCount(1, $fields);
        $this->assertEquals('availability', $fields[0]['name']);
    }

    public function test_all_enum_cases_are_exhaustive(): void
    {
        $expectedCases = [
            'PRICE_RANGE_ALERT', 'PRICE_BELOW_ALERT', 'PRICE_ABOVE_ALERT',
            'AVAILABILITY_ALERT', 'STOCK_STATUS_ALERT', 'SELLER_ALERT',
            'JSON_FIELD_ALERT', 'HTML_SELECTOR_ALERT', 'COMPOSITE_ALERT',
        ];
        $actualCases = array_map(fn ($c) => $c->name, WorkflowType::cases());
        sort($expectedCases);
        sort($actualCases);
        $this->assertEquals($expectedCases, $actualCases);
    }
}
