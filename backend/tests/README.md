# MarketFlow Test Suite Documentation

## Overview

The MarketFlow application uses Laravel's built-in testing framework with PHPUnit/Pest for unit and feature tests, plus BDD-style tests for workflow behavior.

## Test Structure

```
backend/tests/
├── Unit/
│   └── ExampleTest.php          # Basic unit test
├── Feature/
│   ├── ExampleTest.php          # HTTP endpoint tests
│   ├── TorobProcessorTest.php   # Provider processor tests
│   └── WatchWorkflowBDDTest.php # BDD workflow tests
└── TestCase.php                 # Base test case
```

## Running Tests

### Run All Tests
```bash
docker exec marketflow_backend php artisan test
```

### Run Specific Test Class
```bash
docker exec marketflow_backend php artisan test --filter=TorobProcessorTest
docker exec marketflow_backend php artisan test --filter=WatchWorkflowBDDTest
```

### Run by Method Name
```bash
docker exec marketflow_backend php artisan test --filter=test_it_supports_torob_urls
```

## Test Coverage

### TorobProcessorTest (5 tests)

Tests for the TorobProvider marketplace processor:

| Test | Description |
|------|-------------|
| `test_it_supports_torob_urls` | Verifies torob.com URLs are recognized |
| `test_it_does_not_support_non_torob_urls` | Verifies non-torob URLs are rejected |
| `test_it_parses_torob_url_correctly` | Tests URL parsing extracts ID and slug |
| `test_it_fetches_and_normalizes_product_data` | Tests API response processing |
| `test_it_handles_unavailable_products` | Tests handling of out-of-stock items |

### WatchWorkflowBDDTest (8 tests)

BDD-style tests for the price watch workflow:

| Test | Scenario | Expected Result |
|------|----------|-----------------|
| `test_a_new_watch_is_created_with_price_alert_conditions` | Creating a watch with conditions | Watch persisted to database |
| `test_alert_is_triggered_when_price_is_below_threshold` | Price drops below threshold | Alert triggered |
| `test_alert_is_not_triggered_when_price_is_above_threshold` | Price stays above threshold | No alert |
| `test_multiple_conditions_with_and_logic` | Multiple conditions met | Alert triggered |
| `test_alert_when_product_becomes_unavailable` | Product goes out of stock | Notification sent |
| `test_observations_handle_failed_fetches` | API returns 404 | Exception thrown |
| `test_alert_when_price_in_range` | Price within range | Alert triggered |
| `test_alert_with_regex_pattern` | Title matches regex | Alert triggered |

### ExampleTest (2 tests)

Basic application tests:

| Test | Description |
|------|-------------|
| `test_the_application_returns_a_successful_response` | /up endpoint returns 200 |

## Condition Operators

The WorkflowConditionEvaluator supports these operators:

| Operator | Parameters | Example |
|----------|------------|---------|
| `equals` | `value`, `expected_value` | `{"operator": "equals", "value": "availability", "expected_value": "available"}` |
| `not_equals` | `value`, `expected_value` | `{"operator": "not_equals", "value": "status", "expected_value": "deleted"}` |
| `greater_than` | `value`, `expected_value` | `{"operator": "greater_than", "value": "price.amount", "expected_value": 5000000}` |
| `less_than` | `value`, `expected_value` | `{"operator": "less_than", "value": "price.amount", "expected_value": 10000000}` |
| `between` | `value`, `expected_value` (array) | `{"operator": "between", "value": "price.amount", "expected_value": [9000000, 10000000]}` |
| `contains` | `value`, `expected_value` | `{"operator": "contains", "value": "title", "expected_value": "iPhone"}` |
| `matches_regex` | `value`, `expected_value` | `{"operator": "matches_regex", "value": "title", "expected_value": "/iPhone.*Pro/i"}` |

## JSON Path Syntax

Values are extracted from NormalizedProductData using dot notation:

| Path | Accesses |
|------|----------|
| `price.amount` | Price in Rials (integer) |
| `price.currency` | Currency code (IRR) |
| `price.display` | Human-readable price |
| `availability` | Available/unavailable |
| `title` | Product title |
| `seller.name` | Seller name |
| `stockStatus` | Stock status text |

## Writing New Tests

### Unit Test Example
```php
public function test_price_extraction(): void
{
    $rawData = [
        'price' => ['amount' => 9500000, 'currency' => 'IRR']
    ];

    $processor = new TorobProcessor();
    $snapshot = $processor->fetchProduct($reference);
    $normalized = $processor->normalizeSnapshot($snapshot);

    $this->assertEquals(9500000, $normalized->price['amount']);
}
```

### BDD Test Example
```php
/**
 * Given a watch with price-below condition
 * When the product price drops below the threshold
 * Then the alert should be triggered
 */
public function test_alert_is_triggered_when_price_is_below_threshold(): void
{
    Http::fake([
        'torob.com/*' => Http::response([
            'product' => ['title' => 'iPhone 14 Pro'],
            'price' => ['amount' => 8500000],
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

    $this->assertTrue($result);
}
```

### HTTP Fake Example
```php
Http::fake([
    'torob.com/*' => Http::response([
        'product' => [
            'title' => 'Test Product',
            'brand' => 'TestBrand',
        ],
        'price' => [
            'amount' => 5000000,
            'currency' => 'IRR',
            'display' => '۵ میلیون تومان'
        ],
        'availability' => 'available',
        'stock_status' => 'موجود',
    ], 200)
]);
```

## Mocking HTTP Responses

Use Laravel's Http::fake() to mock external API calls:

```php
Http::fake([
    'torob.com/*' => Http::response($data, 200),
    'divar.ir/*' => Http::response(['error' => 'Not found'], 404),
]);
```

## Database Factories

Pre-defined factories for test data:

| Factory | Model | Usage |
|---------|-------|-------|
| UserFactory | User | Authentication tests |
| WatchFactory | Watch | Price alert tests |
| ProductFactory | Product | Product data tests |
| ObservationFactory | Observation | Observation tests |

## Current Test Results

```
Tests:    15 passed (28 assertions)
Duration: 1.15s
```

All tests are green - continuous integration is working correctly.
