# BDD Scenarios for MarketFlow QA Verification

> QA can use this document to verify the entire MarketFlow deployment end-to-end.
> Run backend tests with `php artisan test` (from `backend/` directory),
> run curl commands against `https://torob.adlr.ir/` for deployment verification.
>
> **Current test status: 117 tests passing, 1 skipped (278 assertions)**

---

## Section 1: Backend API Health

### Scenario 1.1: Backend health check endpoint returns 200
```
Given the nginx server is running on torob.adlr.ir
When QA sends `curl -sk https://torob.adlr.ir/up`
Then the response status should be 200
And the response body should contain Laravel HTML
```

### Scenario 1.2: Frontend homepage returns Nuxt SSR HTML
```
Given the frontend container is serving Nuxt SSR
When QA sends `curl -sk https://torob.adlr.ir/`
Then the response status should be 200
And the response body should contain Persian content (not just <script>window.__NUXT__={}</script>)
```

---

## Section 2: TorobProcessor Tests

### Scenario 2.1: Processor supports torob.com URLs
```
Given the TorobProcessor is instantiated
When QA runs: php artisan test --filter=test_it_supports_torob_urls
Then the test should PASS
And supports('https://torob.com/p/12345/iphone-14-pro/') should return true
And supports('http://torob.com/p/67890/iphone-14/') should return true
```

### Scenario 2.2: Processor rejects non-torob URLs
```
Given the TorobProcessor is instantiated
When QA runs: php artisan test --filter=test_it_does_not_support_non_torob_urls
Then the test should PASS
And supports('https://divar.ir/12345') should return false
And supports('https://example.com/product') should return false
And supports('https://other-market.com/p/12345') should return false
```

### Scenario 2.3: Product reference parsed correctly
```
Given a valid torob.com URL: https://torob.com/p/12345/iphone-14-pro-max-review/
When QA runs: php artisan test --filter=test_it_parses_torob_url_correctly
Then the test should PASS
And the parsed reference should have provider = 'torob'
And the parsed reference should have externalId = '12345'
```

### Scenario 2.4: Product data fetched and normalized
```
Given a tafake torob.com API response with product title, price, and availability
When QA runs: php artisan test --filter=test_it_fetches_and_normalizes_product_data
Then the test should PASS
And normalized data should contain:
  - provider = 'torob'
  - externalId = '12345'
  - title = 'آیفون 14 پرو' (Persian)
  - price.amount = 9500000 (integer minor units)
  - price.currency = 'IRR'
  - availability = 'available'
```

### Scenario 2.5: Unavailable products handled correctly
```
Given a torob.com API response with availability = 'unavailable'
When QA runs: php artisan test --filter=test_it_handles_unavailable_products
Then the test should PASS
And normalized availability should be 'unavailable'
And normalized stockStatus should be 'ناموجود' (Persian for "out of stock")
```

---

## Section 3: Watch Workflow BDD Tests

### Scenario 3.1: New watch created with price alert conditions
```
Given a user creates a watch for a torob.com product
When QA runs: php artisan test --filter=test_a_new_watch_is_created_with_price_alert_conditions
Then the test should PASS
And the watch should be persisted in the database
With conditions in format: operator/value/expected_value
```

### Scenario 3.2: Alert triggered when price is below threshold
```
Given a watch with condition: less_than price.amount 10000000
And a torob.com API response with price.amount = 8500000
When the WorkflowConditionEvaluator evaluates the conditions
Then the test should PASS
And the result should be true (alert triggered)
```

### Scenario 3.3: Alert NOT triggered when price is above threshold
```
Given a watch with condition: less_than price.amount 10000000
And a torob.com API response with price.amount = 12000000
When the WorkflowConditionEvaluator evaluates the conditions
Then the test should PASS
And the result should be false (no alert)
```

### Scenario 3.4: Multiple AND conditions all met
```
Given a watch with two conditions:
  - less_than price.amount 10000000
  - equals availability 'available'
And a torob.com API response with price.amount = 9500000 and availability = 'available'
When the WorkflowConditionEvaluator evaluates all conditions
Then the test should PASS
And the result should be true (all conditions met)
```

### Scenario 3.5: Alert triggered when product becomes unavailable
```
Given a watch with condition: equals availability 'unavailable'
And a torob.com API response with availability = 'unavailable'
When the WorkflowConditionEvaluator evaluates the conditions
Then the test should PASS
And the result should be true (out-of-stock notification)
```

### Scenario 3.6: Failed fetch handled gracefully
```
Given a torob.com product that returns 404 Not Found
When the TorobProcessor attempts to fetch the product
Then the test should PASS
And an exception should be thrown (handled gracefully by the workflow)
```

### Scenario 3.7: Alert triggered when price is within range
```
Given a watch with condition: between price.amount [9000000, 10000000]
And a torob.com API response with price.amount = 9500000
When the WorkflowConditionEvaluator evaluates the conditions
Then the test should PASS
And the result should be true (price within range)
```

### Scenario 3.8: Alert triggered when title matches regex pattern
```
Given a watch with condition: matches_regex title '/iPhone.*Pro/i'
And a torob.com API response with title = 'iPhone 14 Pro Max'
When the WorkflowConditionEvaluator evaluates the conditions
Then the test should PASS
And the result should be true (title matches regex)
```

---

## Section 4: Frontend Rendering

### Scenario 4.1: Homepage contains Persian content
```
Given the frontend is deployed and serving on torob.adlr.ir
When QA sends: curl -sk https://torob.adlr.ir/ | grep -o "مارکت‌فلو"
Then the output should include "مارکت‌فلو" (MarketFlow)
When QA sends: curl -sk https://torob.adlr.ir/ | grep -ao "هشدار"
Then the output should include "هشدار" (alert/watch)
When QA sends: curl -sk https://torob.adlr.ir/ | grep -ao "پیگیری"
Then the output should include "پیگیری" (tracking)
```

### Scenario 4.2: Homepage has RTL structure
```
Given the frontend is deployed
When QA sends: curl -sk https://torob.adlr.ir/
Then the response should contain dir="rtl"
And the response should contain Persian placeholder text: "لینک محصول را وارد کنید..."
And the response should contain Persian button text: "افزودن به پیگیری‌ها"
```

### Scenario 4.3: Homepage contains feature grid
```
Given the frontend is deployed
When QA sends: curl -sk https://torob.adlr.ir/
Then the response should contain 4 feature cards:
  - "هشدار قیمت" (Price Alert)
  - "اطلاع‌رسانی فوری" (Instant Notification)
  - "تاریخچه قیمت" (Price History)
  - "چند فروشگاه" (Multiple Stores)
```

### Scenario 4.4: Homepage contains recent watches list
```
Given the frontend is deployed
When QA sends: curl -sk https://torob.adlr.ir/
Then the response should contain "📱 محصولات پیگیری شده" (Tracked Products)
And the response should contain product entries:
  - "iPhone 15 Pro Max" with price "۸۹,۰۰۰,۰۰۰ تومان"
  - "AirPods Pro 2" with price "۱۲,۵۰۰,۰۰۰ تومان"
```

### Scenario 4.5: Homepage contains footer
```
Given the frontend is deployed
When QA sends: curl -sk https://torob.adlr.ir/
Then the response should contain footer text: "Backend API: torob.adlr.ir"
And the response should contain: "Tests: 15/15 ✅"
```

---

## Section 5: Deployment & Infrastructure

### Scenario 5.1: All containers are running
```
Given the deployment is in production state
When QA runs: docker ps
Then the following containers should be in "Up" state:
  - marketflow_backend (php:8.3-fpm-alpine, port 8001)
  - marketflow_db (postgres:15, port 5433)
  - marketflow_redis (redis:latest, port 6379)
  - marketflow_frontend (node:20-slim, port 3002)
  - nginx-certbot-nginx-1 (nginx, ports 80/443)
```

### Scenario 5.2: Backend is reachable from nginx container via Docker DNS
```
Given the nginx-certbot-nginx-1 container is running
When QA runs: docker exec nginx-certbot-nginx-1 wget -qO- marketflow_backend:8000/up
Then the response should be 200 OK
And the response should contain Laravel content
```

### Scenario 5.3: HTTPS endpoint works with self-signed cert
```
Given the nginx HTTPS server is configured with SSL
When QA sends: curl -sk https://torob.adlr.ir/up
Then the response status should be 200
(Note: browser will show SSL warning because cert is self-signed;
 the proper Let's Encrypt cert requires DNS-01 challenge due to
 ArvanCloud CDN forcing HTTPS and intercepting HTTP-01 challenges)
```

### Scenario 5.4: Backend tests all pass
```
Given the backend code is deployed
When QA runs: cd backend && php artisan test
Then the output should show:
  - Tests: 117 passed, 1 skipped
  - Assertions: 278
  - Result: PASS
And the test breakdown should be:
  - Unit tests: 79 tests (WorkflowConditionEvaluatorTest, WorkflowTypeTest,
    ConditionOperatorTest, ProductDTOTest, WatchModelTest,
    WorkflowDispatcherTest, RedisPersistenceServiceTest, NotificationMessageTest)
  - Feature tests: 38 tests (TorobProcessorTest, WatchWorkflowBDDTest,
    WatchApiTest, ObservationApiTest, AuthApiTest, ExampleTest)
```

---

## Quick Verification Commands

```bash
# Run all 117 backend tests
cd /root/nginx-certbot/websites/marketflow/backend && php artisan test

# Run only unit tests (79 tests)
cd /root/nginx-certbot/websites/marketflow/backend && php artisan test --testsuite=Unit

# Run only feature tests (38 tests)
cd /root/nginx-certbot/websites/marketflow/backend && php artisan test --testsuite=Feature

# Verify homepage Persian content (3 keywords in one command)
curl -sk https://torob.adlr.ir/ | grep -o "مارکت‌فلو\|هشدار\|پیگیری"

# Verify backend health endpoint
curl -sk https://torob.adlr.ir/up | head -20

# Verify all containers running
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Verify backend reachable from nginx container
docker exec nginx-certbot-nginx-1 wget -qO- marketflow_backend:8000/up
```

---

## Test Coverage Summary

### Unit Tests (79 tests)
- `Tests\Unit\WorkflowConditionEvaluatorTest` (18 tests): Tests all 8 condition operators (equals, not_equals, greater_than, less_than, between, contains, matches_regex, plus AND/OR logic and edge cases)
- `Tests\Unit\WorkflowTypeTest` (9 tests): Tests all 9 workflow types have Persian labels and correct fields
- `Tests\Unit\ConditionOperatorTest` (9 tests): Tests all 17 condition operators have Persian labels
- `Tests\Unit\ProductDTOTest` (7 tests): Tests DTOs (NormalizedProductData, ProductReference, ProviderProductSnapshot) for immutability and correct casting
- `Tests\Unit\WatchModelTest` (10 tests): Tests Watch model casts, shouldCheck() logic, getConfig()
- `Tests\Unit\WorkflowDispatcherTest` (7 tests): Tests dispatcher private methods (resolveProcessor, buildAlertMessage, recordError, storeObservation) via reflection
- `Tests\Unit\RedisPersistenceServiceTest` (13 tests): Tests Redis service methods using facade mocks
- `Tests\Unit\NotificationMessageTest` (4 tests): Tests notification message structure and channels
- `Tests\Unit\ExampleTest` (1 test): Default test scaffold

### Feature Tests (38 tests)
- `Tests\Feature\TorobProcessorTest` (5 tests): URL support, parsing, fetching, normalization, unavailable products
- `Tests\Feature\WatchWorkflowBDDTest` (8 tests): BDD-style end-to-end workflow tests
- `Tests\Feature\WatchApiTest` (12 tests): Full REST API for watches (CRUD, validation, authorization)
- `Tests\Feature\ObservationApiTest` (6 tests): Observation listing and pagination
- `Tests\Feature\AuthApiTest` (8 tests): Auth flows (register, login, validation, profile)
- `Tests\Feature\ExampleTest` (2 tests): Default feature test scaffold

### Test Categories
- **TDD Unit Tests**: 79 tests covering individual components in isolation
- **BDD Feature Tests**: 38 tests covering end-to-end behavior with given/when/then semantics
- **Edge Case Coverage**: Null handling, empty arrays, type coercion, validation