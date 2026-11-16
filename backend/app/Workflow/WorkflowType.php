<?php

namespace App\Workflow;

enum WorkflowType: string
{
    case PRICE_RANGE_ALERT = 'PRICE_RANGE_ALERT';
    case PRICE_BELOW_ALERT = 'PRICE_BELOW_ALERT';
    case PRICE_ABOVE_ALERT = 'PRICE_ABOVE_ALERT';
    case AVAILABILITY_ALERT = 'AVAILABILITY_ALERT';
    case STOCK_STATUS_ALERT = 'STOCK_STATUS_ALERT';
    case SELLER_ALERT = 'SELLER_ALERT';
    case JSON_FIELD_ALERT = 'JSON_FIELD_ALERT';
    case HTML_SELECTOR_ALERT = 'HTML_SELECTOR_ALERT';
    case COMPOSITE_ALERT = 'COMPOSITE_ALERT';

    public function label(): string
    {
        return match ($this) {
            self::PRICE_RANGE_ALERT => 'هشدار محدوده قیمت',
            self::PRICE_BELOW_ALERT => 'هشدار قیمت کمتر از',
            self::PRICE_ABOVE_ALERT => 'هشدار قیمت بیشتر از',
            self::AVAILABILITY_ALERT => 'هشدار موجودی',
            self::STOCK_STATUS_ALERT => 'هشدار موجودی انبار',
            self::SELLER_ALERT => 'هشدار فروشنده',
            self::JSON_FIELD_ALERT => 'هشدار فیلд JSON',
            self::HTML_SELECTOR_ALERT => 'هشدار انتخابگر HTML',
            self::COMPOSITE_ALERT => 'هشدار ترکیبی',
        };
    }

    public function fields(): array
    {
        return match ($this) {
            self::PRICE_RANGE_ALERT => [
                ['name' => 'min_price', 'component' => 'money-input', 'required' => true],
                ['name' => 'max_price', 'component' => 'money-input', 'required' => true],
            ],
            self::PRICE_BELOW_ALERT => [
                ['name' => 'price', 'component' => 'money-input', 'required' => true],
            ],
            self::PRICE_ABOVE_ALERT => [
                ['name' => 'price', 'component' => 'money-input', 'required' => true],
            ],
            self::AVAILABILITY_ALERT => [
                ['name' => 'availability', 'component' => 'select-input', 'required' => true],
            ],
            self::STOCK_STATUS_ALERT => [
                ['name' => 'stock_status', 'component' => 'text-input', 'required' => true],
            ],
            self::SELLER_ALERT => [
                ['name' => 'seller_name', 'component' => 'text-input', 'required' => true],
            ],
            self::JSON_FIELD_ALERT => [
                ['name' => 'json_path', 'component' => 'text-input', 'required' => true],
                ['name' => 'operator', 'component' => 'select-input', 'required' => true],
                ['name' => 'value', 'component' => 'text-input', 'required' => true],
            ],
            self::HTML_SELECTOR_ALERT => [
                ['name' => 'selector', 'component' => 'text-input', 'required' => true],
            ],
            self::COMPOSITE_ALERT => [
                ['name' => 'conditions', 'component' => 'condition-builder', 'required' => true],
            ],
        };
    }
}
