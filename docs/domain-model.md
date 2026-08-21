# Price Watcher - Domain Model

## Core Entities

### User
- id: UUID/ULID
- name: string
- mobile: string (unique)
- email: string, nullable
- password: string (hashed)
- status: enum (active, inactive, suspended)
- mobile_verified_at: timestamp, nullable
- created_at: timestamp
- updated_at: timestamp

### Product
- id: UUID/ULID
- provider: string (torob, divar, etc.)
- external_id: string (provider-specific product ID)
- canonical_url: string
- title: string, nullable
- image_url: string, nullable
- metadata: JSON, nullable
- last_known_status: string
- created_at: timestamp
- updated_at: timestamp

### Watch
- id: UUID/ULID
- user_id: foreign key to User
- product_id: foreign key to Product
- name: string
- status: enum (active, paused, disabled)
- check_interval_seconds: integer
- next_check_at: timestamp
- last_checked_at: timestamp, nullable
- last_success_at: timestamp, nullable
- last_error_at: timestamp, nullable
- last_error_code: string, nullable
- created_at: timestamp
- updated_at: timestamp

### Workflow
- id: UUID/ULID
- watch_id: foreign key to Watch
- type: enum (workflow type)
- version: integer (for versioning)
- status: enum (active, inactive)
- configuration: JSON
- created_at: timestamp
- updated_at: timestamp

### Observation
- id: UUID/ULID
- watch_id: foreign key to Watch
- provider: string (torob, divar, etc.)
- observed_at: timestamp
- normalized_data: JSON
- raw_data: JSON, nullable
- content_hash: string (for deduplication)
- status: enum (success, error)
- error_code: string, nullable
- error_message: string, nullable
- response_time_ms: integer, nullable

### AlertEvent
- id: UUID/ULID
- watch_id: foreign key to Watch
- workflow_id: foreign key to Workflow
- observation_id: foreign key to Observation
- event_key: string (unique per watch+workflow+state)
- state: enum (condition_not_met, condition_met, notification_pending, notification_sent, notification_failed, resolved)
- triggered_at: timestamp
- resolved_at: timestamp, nullable
- payload: JSON
- created_at: timestamp
- updated_at: timestamp

### Notification
- id: UUID/ULID
- user_id: foreign key to User
- watch_id: foreign key to Watch
- alert_event_id: foreign key to AlertEvent, nullable
- channel: enum (sms, email, push, etc.)
- recipient: string (phone number, email, etc.)
- template: string reference
- payload: JSON
- status: enum (pending, sent, failed, delivered)
- provider_message_id: string, nullable
- sent_at: timestamp, nullable
- failed_at: timestamp, nullable
- failure_reason: string, nullable
- attempts: integer
- created_at: timestamp
- updated_at: timestamp

### ProviderRequestLog
- id: UUID/ULID
- provider: string
- request_hash: string (for deduplication)
- endpoint_identifier: string
- status_code: integer, nullable
- response_time_ms: integer, nullable
- success: boolean
- error_code: string, nullable
- metadata: JSON, nullable
- created_at: timestamp

## Domain Value Objects

### Money
- amount: integer (minor units)
- currency: string (IRR, USD, etc.)
- Methods: add, subtract, compare, format, etc.

### ProductUrl
- value: string
- validation: valid URL, HTTPS only
- Methods: getDomain, getPath, getQueryParams, etc.

### ProviderProductId
- provider: string
- external_id: string

### MobileNumber
- value: string
- validation: Iranian mobile format
- Methods: format, validate, etc.

### CheckInterval
- seconds: integer
- validation: min 300s (5min), max configurable

### ConditionOperator
- enum: equals, not_equals, greater_than, greater_than_or_equal, less_than, less_than_or_equal, between, contains, not_contains, starts_with, ends_with, exists, not_exists, changed, changed_to, changed_from, matches_regex

### WorkflowType
- enum: PRICE_RANGE_ALERT, PRICE_BELOW_ALERT, PRICE_ABOVE_ALERT, AVAILABILITY_ALERT, STOCK_STATUS_ALERT, SELLER_ALERT, JSON_FIELD_ALERT, HTML_SELECTOR_ALERT, COMPOSITE_ALERT

### NotificationChannel
- enum: sms, email, push, webhook

### ProviderName
- enum: torob, divar (future)

## Relationships

### User ↔ Watch
- One-to-Many: User has many Watches
- Foreign Key: Watch.user_id references User.id
- Cascade: On User delete, Watch delete (or soft delete)

### Product ↔ Watch
- One-to-Many: Product has many Watches (different users can watch same product)
- Foreign Key: Watch.product_id references Product.id
- Cascade: On Product delete, Watch set null or restrict

### Watch ↔ Workflow
- One-to-Many: Watch has many Workflows (different alert configurations)
- Foreign Key: Workflow.watch_id references Watch.id
- Cascade: On Watch delete, Workflow delete

### Watch ↔ Observation
- One-to-Many: Watch has many Observations
- Foreign Key: Observation.watch_id references Watch.id
- Cascade: On Watch delete, Observation delete

### Watch ↔ AlertEvent
- One-to-Many: Watch has many AlertEvents
- Foreign Key: AlertEvent.watch_id references Watch.id
- Cascade: On Watch delete, AlertEvent delete

### Workflow ↔ AlertEvent
- One-to-Many: Workflow has many AlertEvents
- Foreign Key: AlertEvent.workflow_id references Workflow.id
- Cascade: On Workflow delete, AlertEvent delete

### AlertEvent ↔ Notification
- One-to-Many: AlertEvent has many Notifications (retry attempts)
- Foreign Key: Notification.alert_event_id references AlertEvent.id
- Cascade: On AlertEvent delete, Notification delete

### User ↔ Notification
- One-to-Many: User has many Notifications
- Foreign Key: Notification.user_id references User.id
- Cascade: On User delete, Notification delete

### Provider ↔ ProviderRequestLog
- One-to-Many: Provider has many ProviderRequestLogs
- Foreign Key: ProviderRequestLog.provider references Provider.name
- No cascade (logs persist)

## Database Constraints

### Unique Constraints
- users.mobile (unique)
- watches.id (primary key)
- products.id (primary key)
- workflows.id (primary key)
- observations.id (primary key)
- alert_events.id (primary key)
- alert_events.event_key (unique per watch+workflow)
- notifications.id (primary key)
- provider_request_logs.id (primary key)

### Foreign Key Constraints
- watches.user_id → users.id
- watches.product_id → products.id
- workflows.watch_id → watches.id
- observations.watch_id → watches.id
- alert_events.watch_id → watches.id
- alert_events.workflow_id → workflows.id
- alert_events.observation_id → observations.id
- notifications.user_id → users.id
- notifications.watch_id → watches.id
- notifications.alert_event_id → alert_events.id

### Check Constraints
- watch.status IN ('active', 'paused', 'disabled')
- workflow.status IN ('active', 'inactive')
- observation.status IN ('success', 'error')
- alert_event.state IN ('condition_not_met', 'condition_met', 'notification_pending', 'notification_sent', 'notification_failed', 'resolved')
- notification.status IN ('pending', 'sent', 'failed', 'delivered')
- provider_request_logs.success IN (true, false)
- watch.check_interval_seconds >= 300 (5 minutes minimum)

## JSON Schema Examples

### Watch Configuration (in Watch.name or separate field)
```json
{
  "display_name": "My Product Price Watch",
  "notes": "Want to buy when price drops below 10M IRR"
}
```

### Workflow Configuration
```json
{
  "type": "PRICE_RANGE_ALERT",
  "version": 1,
  "conditions": {
    "type": "all",
    "conditions": [
      {
        "field": "price.amount",
        "operator": "between",
        "value": { "min": 5000000, "max": 15000000, "currency": "IRR" }
      },
      {
        "field": "availability",
        "operator": "equals",
        "value": "available"
      }
    ]
  },
  "actions": [
    {
      "type": "notification",
      "channel": "sms",
      "template": "price_alert"
    }
  ],
  "deduplication": {
    "strategy": "state_change",
    "cooldown_minutes": 60,
    "max_per_day": 5
  },
  "schedule": {
    "enabled": true,
    "timezone": "Asia/Tehran"
  }
}
```

### Normalized Product Data
```json
{
  "provider": "torob",
  "external_id": "12345678",
  "title": "Samsung Galaxy S23 Ultra",
  "price": {
    "amount": 9500000,
    "currency": "IRR",
    "display": "۹٫۵۰۰٫۰۰۰ تومان"
  },
  "availability": "available",
  "stock_status": "موجود",
  "seller": {
    "name": "Digital Store",
    "url": "https://torob.com/seller/digital-store"
  },
  "badges": ["original_warranty", "fast_delivery"],
  "image_url": "https://static.torob.com/images/product/12345678.jpg",
  "product_url": "https://torob.com/p/12345678/samsung-galaxy-s23-ultra/",
  "observed_at": "2026-08-20T10:30:00Z",
  "raw": {
    "api_response": { /* raw provider response */ }
  }
}
```

### AlertEvent Payload
```json
{
  "trigger_reason": "price_below_threshold",
  "condition_details": {
    "field": "price.amount",
    "operator": "less_than",
    "threshold": 10000000,
    "current_value": 9500000
  },
  "product_snapshot": {
    "title": "Samsung Galaxy S23 Ultra",
    "price": { "amount": 9500000, "currency": "IRR" },
    "availability": "available"
  }
}
```

### Notification Payload
```json
{
  "watch_name": "My Phone Price Watch",
  "product_title": "Samsung Galaxy S23 Ultra",
  "trigger_reason": "price_below_threshold",
  "current_price": { "amount": 9500000, "currency": "IRR", "display": "۹٫۵۰۰٫۰۰۰ تومان" },
  "product_url": "https://torob.com/p/12345678/samsung-galaxy-s23-ultra/",
  "app_name": "Price Watcher"
}
```

## Indexes for Performance

### Composite Indexes
- watches: (status, next_check_at) - for scheduler query
- watches: (user_id, status) - for user dashboard
- products: (provider, external_id) - for product lookup/deduplication
- observations: (watch_id, observed_at) - for time-series queries
- notifications: (user_id, created_at) - for notification history
- alert_events: (watch_id, event_key) - for deduplication checks
- provider_request_logs: (provider, created_at) - for provider metrics

### Single Column Indexes
- users: mobile (unique)
- watches: user_id
- watches: product_id
- workflows: watch_id
- observations: watch_id
- alert_events: watch_id
- alert_events: workflow_id
- alert_events: observation_id
- notifications: watch_id
- notifications: alert_event_id
- provider_request_logs: provider

## Soft Delete Considerations

### When to Use Soft Delete
- User accounts (for potential recovery)
- Watches (user might want to restore)
- Workflows (configuration history)

### When NOT to Use Soft Delete
- Observations (keep all for analytics)
- AlertEvents (audit trail)
- Notifications (delivery history)
- ProviderRequestLogs (metrics and debugging)

### Implementation
- Use Laravel's SoftDeletes trait where appropriate
- Add deleted_at timestamp column
- Modify queries to exclude soft-deleted records by default
- Provide restore functionality where needed

## Domain Events

### Events to Consider
- UserRegistered
- UserMobileVerified
- WatchCreated
- WatchUpdated
- WatchDeleted
- WatchActivated
- WatchPaused
- WorkflowCreated
- WorkflowUpdated
- WorkflowDeleted
- ObservationCreated
- AlertEventTriggered
- NotificationSent
- NotificationFailed
- ProviderErrorOccurred
- ProviderRecovered

### Event Handling
- Use Laravel's event system
- Async processing via queues where appropriate
- Sync processing for immediate consistency needs
- Event sourcing consideration for audit trails

## Aggregates and Boundaries

### Aggregate Roots
- User (contains Watches)
- Product (referenced by Watches)
- Watch (contains Workflows, Observations, AlertEvents, Notifications)
- Workflow (contains AlertEvents)
- ProviderRequestLog (standalone)

### Bounded Contexts
- Identity Context: User management, authentication, authorization
- Catalog Context: Product information, providers
- Monitoring Context: Watches, observations, scheduling
- Workflow Context: Alert rules, condition evaluation
- Notification Context: Message delivery, channels
- Provider Context: External service integration
- Admin Context: Administrative functions
- Context mapping: Anti-corruption layers where contexts interact

## Security Considerations in Domain Model

### Data Protection
- PII: mobile numbers, email addresses (encrypted at rest if required)
- Passwords: hashed with bcrypt/argon2
- Financial data: monetary values as integers (no floating point)
- Secrets: never stored in domain model (use environment/vault)

### Validation
- All entities validate invariants on creation/update
- Value objects enforce business rules
- Input sanitization at boundaries
- Output encoding where applicable

### Immutability
- Value objects are immutable
- Entities have controlled mutation through methods
- History preserved through events and snapshots

## Extensibility Points

### Adding New Providers
1. Implement MarketplaceProvider interface
2. Add provider to ProviderName enum
3. Register provider in ProviderRegistry
4. Add provider-specific config to .env
5. Update API to show provider in UI

### Adding New Workflow Types
1. Add to WorkflowType enum
2. Create condition evaluators if needed
3. Add frontend form schema
4. Add default configuration templates
5. Update documentation

### Adding New Notification Channels
1. Implement NotificationChannel interface
2. Add to NotificationChannel enum
3. Add channel-specific config
4. Update frontend to support channel
5. Add channel to admin notification management

### Adding New Condition Operators
1. Add to ConditionOperator enum
2. Implement ConditionEvaluator
3. Register in ConditionEvaluatorRegistry
4. Update frontend condition builder
5. Add unit tests

## Data Retention Policies

### Configurable Retention
- Raw response retention: default 7 days
- Observation retention: default 30 days
- Notification retention: default 90 days
- Provider request log retention: default 60 days
- Alert event retention: default 365 days

### Retention Implementation
- Scheduled commands to purge old data
- Archive before deletion (optional)
- Metrics on data volume over time
- GDPR compliance considerations

## Performance Considerations

### Caching Strategies
- Product metadata cache (short-term)
- Provider health status cache
- Workflow configuration cache
- Notification template cache
- Avoid caching sensitive user data

### Database Optimization
- Read replicas for observation queries
- Partitioning observations by date
- Index maintenance schedule
- Connection pooling
- Query monitoring and optimization

### Horizontal Scaling
- Stateless application servers
- Shared database with read replicas
- Redis for shared cache (when enabled)
- Shared storage for uploads
- Load balancer with sticky sessions if needed

## Internationalization and Localization

### Language
- UI: Persian (Farsi)
- Code comments: English
- Database: UTF-8 encoding
- API: English for error codes, Persian for user messages

### Formatting
- Numbers: Persian/Arabic-Indic digits
- Dates: Persian calendar option, Gregorian with Persian localization
- Currency: IRR with proper formatting (۹٫۵۰۰٫۰۰۰ تومان)
- Text direction: RTL

### Future Extensibility
- Language files for UI strings
- Locale-aware formatting services
- Timezone storage and conversion
- Right-to-left layout support in all components

## Edge Cases and Error Handling

### Provider-Specific Edge Cases
- Product deleted/unavailable
- Price temporarily unavailable
- Rate limiting (HTTP 429)
- CAPTCHA challenges
- Service downtime (HTTP 5xx)
- Invalid/malformed responses
- Schema changes from provider
- Timeout on provider requests

### System-Wide Edge Cases
- Database connection failures
- Queue worker crashes
- Scheduler misfire
- Disk space exhaustion
- Memory leaks
- Concurrent updates to same watch
- Clock drift/distributed time issues

### Error Handling Patterns
- Graceful degradation (mark provider unavailable)
- Circuit breaker pattern for providers
- Retry with exponential backoff
- Dead letter queues for failed jobs
- Alerting on systemic errors
- User-friendly error messages
- Admin dashboard for system health