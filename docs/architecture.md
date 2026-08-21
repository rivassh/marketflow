# Price Watcher - Architecture Decision Records

## Project Overview
Production-ready MVP for a price and product-condition alert platform.
Users submit Torob product URLs, configure conditions, and receive SMS notifications when conditions are met.

## Core Architecture Principles
- Domain-Driven Design (DDD)
- SOLID Principles
- DRY (Don't Repeat Yourself)
- Modular Monolith
- Workflow-based
- Adapter-based external integrations
- Testable and maintainable
- Resource-efficient for MVP
- Security-first
- API-first

## Technology Stack

### Backend
- PHP 8.3+
- Laravel 13 (or latest stable)
- Laravel Sanctum for SPA/API authentication
- Laravel Queues (database driver for MVP)
- Laravel Scheduler
- Laravel Notifications
- Laravel Events and Listeners
- Laravel HTTP Client
- Laravel Cache (database driver for MVP)
- Laravel RateLimiter

### Frontend
- Vue 3
- Nuxt 3 (latest stable, no alpha/beta/canary)
- Tailwind CSS (latest stable compatible with Nuxt)
- TypeScript
- Pinia (only if state management required)
- VueUse (only if clear value)

### Database
- PostgreSQL (production)
- SQLite (local development and automated tests)
- ULID identifiers (preferred over UUID)
- Monetary values stored as integer minor units (never floating point)

### Infrastructure
- Docker Compose
- PHP-FPM
- Nginx
- PostgreSQL
- Optional Redis (production optimization)
- Database queue and database cache by default
- Non-root containers where possible
- Healthchecks
- Named volumes
- Environment variables
- Resource limits

### SMS
- Provider abstraction
- Fake/local provider for testing
- Configurable Iranian SMS provider adapter
- Configurable through environment variables
- Never hard-code credentials

### UI/UX
- All UI text in Persian
- All source-code comments in English
- RTL layout
- Persian-friendly number and date formatting

## Module Structure (Modular Monolith)

```
app/
  Domain/
    Identity/
    Catalog/
    Monitoring/
    Workflow/
    Notification/
    Provider/
    Shared/
  Application/
    Identity/
    Catalog/
    Monitoring/
    Workflow/
    Notification/
    Provider/
  Infrastructure/
    Persistence/
    Providers/
    Notifications/
    Scheduling/
    Logging/
  Http/
    Controllers/
    Requests/
    Resources/
    Middleware/
```

### Module Responsibilities

#### Identity
- Registration, Login, Logout
- Email/phone verification
- User profile
- User notification preferences

#### Catalog
- Product
- Product source
- Canonical product URL
- External provider product ID
- Product metadata
- Product snapshot

#### Monitoring
- Watch creation
- Watch activation/deactivation
- Check frequency
- Check execution
- Observation history
- Last successful observation
- Last failed observation

#### Workflow
- Workflow definitions
- Workflow versions
- Trigger conditions
- Rule groups
- Rule operators
- Workflow execution
- Workflow result

#### Notification
- Notification channel
- SMS delivery
- Delivery status
- Retry
- Deduplication
- Notification templates

#### Provider
- Source provider interface
- Torob adapter
- Future Divar adapter
- Provider response normalization
- Provider health checks

#### Shared
- Value objects
- Domain events
- Exceptions
- Result objects
- Clock abstraction
- Money value object
- URL value object
- JSON path utilities
- Pagination and filtering

## Provider Abstraction

### MarketplaceProvider Interface
```php
interface MarketplaceProvider {
    public function supports(ProductUrl $url): bool;
    public function parseProductReference(ProductUrl $url): ProductReference;
    public function fetchProduct(ProductReference $reference): ProviderProductSnapshot;
    public function normalizeSnapshot(ProviderProductSnapshot $snapshot): NormalizedProductData;
}
```

### ProviderRegistry
```php
interface ProviderRegistry {
    public function resolveByUrl(ProductUrl $url): MarketplaceProvider;
}
```

### Torob Adapter Requirements
- Parse URLs: `https://torob.com/p/{product-id}/{slug}/`
- Extract product ID (not rely on slug)
- Support configuration: Base URL, API endpoints, timeouts, retry, backoff, rate limit, cache TTL, User-Agent, headers, enable/disable flag
- No raw cookies or user session data in database
- Normalize provider responses to common structure

### Normalized Product Data Structure
```json
{
  "provider": "torob",
  "external_id": "...",
  "title": "...",
  "price": {
    "amount": 9500000,
    "currency": "IRR",
    "display": "۹٫۵۰۰٫۰۰۰ تومان"
  },
  "availability": "available|unavailable|unknown",
  "stock_status": "...",
  "seller": { "name": "...", "url": null },
  "badges": [],
  "image_url": "...",
  "product_url": "...",
  "observed_at": "...",
  "raw": {}
}
```

## Workflow System

### Core Concepts
- Trigger
- Conditions
- Action
- Deduplication policy
- Cooldown policy
- Schedule policy

### Enums
- WorkflowType: PRICE_RANGE_ALERT, PRICE_BELOW_ALERT, PRICE_ABOVE_ALERT, AVAILABILITY_ALERT, STOCK_STATUS_ALERT, SELLER_ALERT, JSON_FIELD_ALERT, HTML_SELECTOR_ALERT, COMPOSITE_ALERT
- ConditionOperator: equals, not_equals, greater_than, greater_than_or_equal, less_than, less_than_or_equal, between, contains, not_contains, starts_with, ends_with, exists, not_exists, changed, changed_to, changed_from, matches_regex
- ActionType
- NotificationChannel
- DeduplicationStrategy
- WorkflowStatus

### Condition Evaluator
```php
interface ConditionEvaluator {
    public function evaluate(NormalizedProductData $data, Condition $condition): ConditionResult;
}

interface ConditionEvaluatorRegistry {
    public function resolve(ConditionField $field): ConditionEvaluator;
}
```

### Safe JSON Path Reader
For accessing nested fields in normalized data.

### Safety Requirements
- No eval
- No arbitrary PHP code execution
- No arbitrary shell commands
- No unrestricted JavaScript execution

## Alert State and Deduplication

### State Transitions
- condition_not_met
- condition_met
- notification_pending
- notification_sent
- notification_failed
- resolved

### Default Behavior
- Send notification only when condition changes from false to true
- Do not send every scheduler run
- If condition becomes false then true again, allow new notification
- Optional cooldown in minutes
- Optional repeat notification interval
- Maximum notification count per day
- Deterministic event_key: sha256(watch_id + workflow_id + condition_state + normalized relevant values)
- Database constraints and idempotency locks to prevent duplicates

## Scheduling and Resource Efficiency

### MVP Defaults
- Minimum check interval: 5 minutes
- Default interval: 15 minutes
- Maximum interval: configurable by admin
- Request timeout: 10 seconds
- Retry attempts: 2
- Raw response retention: configurable
- Observation retention: configurable

### Scheduler Command
`php artisan monitor:dispatch-due`
1. Find due active watches
2. Lock rows safely
3. Dispatch one job per watch
4. Update next_check_at
5. Avoid dispatching duplicates

### CheckWatchJob
1. Load watch and workflow
2. Resolve provider
3. Fetch product data
4. Normalize response
5. Store observation
6. Evaluate workflow
7. Create AlertEvent if needed
8. Dispatch notification delivery if needed
9. Update watch state
10. Log metrics and errors

### Infrastructure
- One scheduler process
- One queue worker
- Queue uniqueness or database locking
- Retry with exponential backoff
- Timeout
- Maximum attempts
- Provider circuit breaker
- Per-provider rate limiting
- Cache product observations when possible

## Notification System

### NotificationChannel Interface
```php
interface NotificationChannel {
    public function send(NotificationMessage $message): DeliveryResult;
}
```

### Implementations
- FakeSmsChannel (testing)
- Configurable IranianSmsChannel
- NullChannel (development)

### SMS Message Content
- Product title
- Current price
- Trigger reason
- Product URL
- Application name
- Optional unsubscribe/settings link

### Features
- Delivery log
- Retry
- Idempotency
- Provider response storage
- Failure handling
- Per-user rate limit
- Daily notification limit
- Admin-configurable SMS template

## Authentication and Authorization

### Implementation
- User registration with mobile number
- Login, Logout
- Password reset or OTP-based login
- Mobile verification
- Sanctum authentication
- Session management
- Rate limiting
- CSRF protection
- Strong password rules
- Authorization policies

### Roles
- user
- admin

### Policies
- Watch access
- Workflow access
- Observation access
- Notification access
- Admin resources

### Security
- Users never access another user's data
- Admin routes protected

## Admin Panel (Nuxt)

### Features
1. Dashboard: Total users, Active watches, Checks 24h, Provider failures, Notifications sent/failed
2. User management: Search, Filter, Suspend/activate, View watches, View notifications
3. Watch management: View all, Pause/resume, Force check, View last error, Check history
4. Provider management: Enable/disable, Health, Request stats, Rate limits, Cache TTL
5. Workflow management: View types, Enable/disable, Versions, Defaults
6. Notification management: SMS provider settings, Test SMS, Templates, Daily limits, Failed deliveries
7. System settings: Default/max check interval, Retention, Maintenance mode, Feature flags

### Technical
- Server-side pagination and filtering
- Do not load all records into browser

## User Panel (Nuxt, Persian RTL)

### Pages
- Login, Register, Verify mobile
- Dashboard
- Create watch, Watch details
- Edit workflow
- Observation history
- Notification history
- Profile, Notification settings
- API error and provider status views

### Main User Flow
1. Enter product URL
2. Frontend calls product preview endpoint
3. Backend detects provider
4. Backend parses product reference
5. Backend fetches preview through provider adapter
6. UI shows: Product title, Image, Current price, Availability, Provider
7. User chooses workflow template
8. User configures conditions
9. User enters/confirms mobile number
10. User saves watch
11. Dashboard shows watch status and next check time

### Error Handling
- Invalid URL, Unsupported provider
- CAPTCHA or provider unavailable
- Rate limit, Timeout
- Product not found, Empty response
- Validation errors
- Loading state, Retry state

## API Design

### Versioning
- `/api/v1`

### Endpoints
- Auth: POST /auth/register, POST /auth/login, POST /auth/logout, GET /auth/me, POST /auth/verify-mobile
- Product Preview: POST /product-previews, GET /product-previews/{id}
- Watches: GET/POST /watches, GET/PATCH/DELETE /watches/{watch}, POST /watches/{watch}/pause|resume|check-now
- Workflows: GET /workflow-types, GET/POST /watches/{watch}/workflows, PATCH/DELETE /workflows/{workflow}, POST /workflows/{workflow}/validate
- Observations: GET /watches/{watch}/observations
- Notifications: GET /notifications, GET /notifications/{notification}
- Admin: GET /admin/dashboard, GET /admin/users, GET /admin/watches, GET /admin/providers, GET /admin/notifications, POST /admin/watches/{watch}/check-now

### Standards
- Form Requests
- API Resources
- OpenAPI documentation
- Consistent error response format
- Request IDs
- Pagination, Filtering, Sorting
- Authorization, Rate limiting

### Error Response Format
```json
{
  "message": "Unable to fetch product data.",
  "code": "PROVIDER_UNAVAILABLE",
  "request_id": "..."
}
```

## Database Requirements

### Migrations
- Foreign keys
- Proper indexes
- Unique constraints
- Check constraints where supported
- JSON columns for extensible configuration
- Soft deletes only where justified

### Important Indexes
- watches(status, next_check_at)
- watches(user_id, status)
- products(provider, external_id)
- observations(watch_id, observed_at)
- notifications(user_id, created_at)
- alert_events(watch_id, event_key)
- provider_request_logs(provider, created_at)

### Transactions
- Watch creation
- Workflow updates
- Alert event creation
- Notification creation

### Locking
- Optimistic or pessimistic where required

## Workflow Scaffolding Command

### Artisan Command
```bash
php artisan make:workflow {name}
    {--provider=}
    {--fields=}
    {--force}
```

### Generated Files
- Workflow enum entry
- Workflow configuration class
- Condition field definitions
- Evaluator class
- Action class if needed
- DTOs
- Tests
- Frontend workflow metadata
- Frontend form schema
- Documentation stub

### Generator Requirements
- Convert kebab-case to valid PHP class names
- Refuse overwriting unless --force
- Validate names
- Update registries safely
- Generate tests
- Generate frontend form configuration
- Generate README/documentation file

## Frontend Workflow Schema

### Metadata-Driven Forms
Frontend renders workflow forms from metadata rather than hard-coded logic.

### Reusable Components
- BaseInput, MoneyInput, SelectInput, ToggleInput
- ConditionBuilder, ConditionGroup
- WorkflowForm, ProductPreviewCard
- WatchStatusBadge, ObservationChart
- NotificationStatusBadge, DataTable
- ConfirmDialog, Toast, EmptyState, ErrorState

### TypeScript Types
Generated or manually synchronized from backend API schema.

## Observability

### MVP Implementation
- Structured application logs
- Request ID
- Provider request metrics
- Job success/failure logs
- Notification metrics
- Health endpoint
- Readiness endpoint
- Basic Laravel Telescope only in local/staging

### Extension Points
- Prometheus
- Grafana
- ELK
- Sentry

### Logging
- Provider, Watch ID, Workflow ID, Job ID, Request ID
- Duration, Result status, Error code
- Never log: passwords, SMS credentials, access tokens, full cookies, sensitive PII, unmasked phone numbers

## Security Requirements

### OWASP Best Practices
- Input validation
- Authorization policies
- Rate limiting
- SSRF prevention
- URL allowlist by provider
- Private IP blocking
- DNS rebinding protection
- Maximum response size
- Timeout
- Safe JSON parsing
- No eval, arbitrary code/shell execution
- Secure secrets through environment variables
- Password hashing
- Secure session configuration
- Security headers
- Audit log for admin actions
- Mass assignment protection
- Output escaping
- XSS protection
- SQL injection protection through framework query APIs

### External URL Security
- Accept only HTTPS
- Allow only configured domains
- Reject localhost
- Reject private and link-local IPs
- Reject file://, ftp://, gopher://
- Prevent redirects to disallowed domains
- Limit redirect count

## Testing Strategy

### Backend
- Unit tests: value objects, condition evaluators, workflow state transitions
- Feature tests: authentication, watch CRUD, authorization
- Provider contract tests
- Fake provider tests
- Queue/job tests
- Notification tests
- Idempotency tests
- Scheduler tests
- SSRF security tests

### Frontend
- Component tests for reusable controls
- Workflow form tests
- Condition builder tests
- API error-state tests
- Authentication flow tests
- E2E tests (Playwright): Register, Login, Add Torob product, Configure price range, Pause/resume, View observation, Admin login, Admin force check

### Tools
- Pest PHP, PHPUnit
- Laravel HTTP fake, Queue fake, Notification fake
- Playwright for E2E
- ESLint, Prettier
- PHPStan/Larastan
- Pint

### Quality Gates
- No failing tests
- No PHPStan/Larastan errors at configured level
- No ESLint errors
- No TypeScript errors
- No formatting errors

## Docker and Local Development

### Compose Files
- compose.yaml
- compose.dev.yaml
- compose.prod.example.yaml

### Services for MVP
- app, queue, scheduler, database, frontend
- Optional: redis, mailpit, adminer

### Configuration
- Non-root containers
- Healthchecks
- Named volumes
- Environment variables
- Resource limits
- Separate development/production config
- No secrets in Git

### Provided Files
- .env.example
- Makefile or Taskfile
- README.md
- Database setup instructions
- Test, queue, scheduler instructions
- Production deployment instructions

### Suggested Commands
- make install, make up, make down
- make test, make lint, make format
- make migrate, make seed
- make worker, make scheduler

## Seed Data
- Admin user, Normal user
- Fake Torob product
- Sample price range workflow
- Sample availability workflow
- Sample observations, notifications
- All seed credentials marked as local-development-only

## Implementation Phases

### PHASE 0 — Discovery and Architecture
- Inspect existing repository
- Detect current Laravel, Nuxt, Vue, Tailwind, PHP, Node versions
- Confirm project structure
- Create architecture decision records
- Create bounded-context map
- Create initial ERD
- Create API contract draft
- Create implementation plan
- Do not implement business logic yet

### PHASE 1 — Project Bootstrap
- Initialize Laravel backend
- Initialize Nuxt frontend
- Configure Tailwind, TypeScript
- Configure Docker Compose
- Configure PostgreSQL and SQLite testing
- Configure Pint, PHPStan/Larastan, ESLint, Prettier
- Configure environment files
- Add health endpoint
- Add base layouts and RTL direction

### PHASE 2 — Identity and Access
- User model, Registration, Login, Logout
- Sanctum, Mobile verification abstraction
- Roles, Admin middleware, Policies
- Rate limits, Basic auth pages

### PHASE 3 — Core Domain and Persistence
- Product, Watch, Workflow, Observation, AlertEvent, Notification, ProviderRequestLog
- Migrations, Repositories, DTOs, Value objects, Domain events

### PHASE 4 — Provider System
- MarketplaceProvider contract, ProviderRegistry
- Torob URL parser, Torob adapter, Fake provider
- HTTP client configuration, Cache, Rate limit
- Retry/backoff, Timeout, Circuit breaker
- Provider error mapping

### PHASE 5 — Workflow Engine
- Workflow enums, Condition model, Condition parser
- Condition evaluator registry, All/any groups
- Numeric/string comparisons, Exists/not exists
- Changed/changed_to/changed_from
- Safe JSON path access, Workflow validation endpoint

### PHASE 6 — Alert State and Deduplication
- AlertEvent state machine
- Event key generation
- Deduplication logic
- Cooldown and repeat policies
- Idempotency locks

### PHASE 7 — Scheduling and Check Execution
- monitor:dispatch-due command
- CheckWatchJob
- Provider integration in job
- Observation storage
- Workflow evaluation in job
- Watch state updates

### PHASE 8 — Notification System
- NotificationChannel interface
- FakeSmsChannel, IranianSmsChannel
- Delivery log, Retry, Idempotency
- Templates, Rate limits, Daily limits
- Admin SMS test endpoint

### PHASE 9 — API Layer
- API versioning
- Controllers, Form Requests, Resources
- OpenAPI documentation
- Error responses, Request IDs
- Pagination, Filtering, Sorting
- Authorization, Rate limiting

### PHASE 10 — Admin Panel
- Dashboard, User management
- Watch management, Provider management
- Workflow management, Notification management
- System settings

### PHASE 11 — User Panel
- Auth pages, Dashboard
- Create watch, Watch details
- Edit workflow, Observation history
- Notification history, Profile
- Notification settings, Error views

### PHASE 12 — Integration and E2E
- Full integration tests
- Playwright E2E tests
- Performance baseline
- Security scan

### PHASE 13 — Production Hardening
- Production Docker config
- SSL/TLS
- Monitoring, Alerting
- Backup strategy
- Documentation

## Known Limitations (Phase 0)
- No business logic implemented yet
- No code exists beyond this documentation
- Docker, Laravel, Nuxt projects not yet initialized
- All deliverables are planning artifacts only