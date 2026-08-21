# Workflow System

## Overview
The workflow system is the core extensibility mechanism. A workflow consists of:
- Trigger
- Conditions
- Action
- Deduplication policy
- Cooldown policy
- Schedule policy

## Enums
- **WorkflowType**: PRICE_RANGE_ALERT, PRICE_BELOW_ALERT, PRICE_ABOVE_ALERT, AVAILABILITY_ALERT, STOCK_STATUS_ALERT, SELLER_ALERT, JSON_FIELD_ALERT, HTML_SELECTOR_ALERT, COMPOSITE_ALERT
- **ConditionField**: price.amount, price.currency, availability, stock_status, seller.name, etc.
- **ConditionOperator**: equals, not_equals, greater_than, greater_than_or_equal, less_than, less_than_or_equal, between, contains, not_contains, starts_with, ends_with, exists, not_exists, changed, changed_to, changed_from, matches_regex
- **ActionType**: notification, webhook, etc.
- **NotificationChannel**: sms, email, etc.
- **DeduplicationStrategy**: event_key based
- **WorkflowStatus**: active, paused, disabled

## Condition Evaluator
```php
interface ConditionEvaluator {
    public function evaluate(NormalizedProductData $data, Condition $condition): ConditionResult;
}
```

## Evaluator Registry
```php
interface ConditionEvaluatorRegistry {
    public function resolve(ConditionField $field): ConditionEvaluator;
}
```

## Condition Configuration Examples
```json
{
  "type": "all",
  "conditions": [
    {
      "field": "price.amount",
      "operator": "between",
      "value": { "min": 10000000, "max": 15000000, "currency": "IRR" }
    },
    {
      "field": "availability",
      "operator": "equals",
      "value": "available"
    }
  ]
}
```

Nested groups supported:
```json
{
  "type": "any",
  "conditions": [
    { "type": "all", "conditions": [] },
    {
      "field": "stock_status",
      "operator": "contains",
      "value": "موجود"
    }
  ]
}
```

## JSON Path Reader
Safe JSON path access for normalized product data.

## HTML Selector Conditions
- Use provider-approved HTML only
- Sanitize and limit selector complexity
- Set maximum response size and timeout
- Store selector configuration separately

## Safe Evaluation Rules
- Do not execute arbitrary PHP code
- Do not use `eval`
- Do not allow arbitrary shell commands
- Do not allow unrestricted JavaScript execution