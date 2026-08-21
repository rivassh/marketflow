# Price Watcher - Entity Relationship Diagram

## Overview

This ERD documents the database schema for the Price Watcher MVP. All primary keys use ULID identifiers. Foreign key relationships are maintained with proper constraints and indexes.

## Entities

```
┌─────────────┐     ┌──────────────┐
│    User     │     │   Product    │
├─────────────┤     ├──────────────┤
│ id (PK)     │     │ id (PK)      │
│ name        │     │ provider     │
│ mobile      │     │ external_id │
│ email       │     │ canonical_url│
│ password    │     │ title        │
│ status      │     │ image_url    │
│ ...         │     │ metadata     │
└─────────────┘     │ last_known_status│
                    │ ...            │
                    └──────────────┘
```

## Core Relationships

```
                            ┌──────────────┐
                            │   Product    │
                            ├──────────────┤
                            │ id (PK)      │
                            │ provider     │
                            │ external_id │
                            │ canonical_url│
                            │ title        │
                            └──────┬───────┘
                                   │ (1)
                                   │
┌─────────────┐ (N)              │ (M:N)   (N)
│    User     │◄────────────────┴───────────►│ Product │
├─────────────┤                                    (through Watches)
│ id (PK)     │                                    │
│ mobile      │                                    │
│ ...         │                                    │
└──────┬──────┘                                    │
       │                                           │
       │ (1)                                       │
       │                                           │
       │    ┌──────────────┐                       │
       └────►    Watch     ◄───────────────────────┘
              ├──────────────┤
              │ id (PK)      │
              │ user_id (FK) │
              │ product_id(FK)│
              │ name        │
              │ status      │
              │ ...         │
              └──────┬─────┘
                     │ (1)
                     │
                     │    ┌──────────────┐         (N)
                     └────►   Workflow   ◄──────────────────┐
                  (N) │              (1)                     │
                      │ id (PK)      │ id (PK)                 │
                      │ watch_id (FK)  │ watch_id (FK)         │
                      │ type          │ workflow_id (FK)     ┌─▼─────────────┐
                      │ version       │ observation_id (FK)  │ AlertEvent    │
                      │ status        │ event_key (UK)       ├───────────────┤
                      │ config        │ state               │ id (PK)       │
                      │ ...           │ triggered_at        │ watch_id (FK) │
                      └──────────────┘ resolved_at         │ workflow_id(FK)│
                                                           │ observation_id(FK)│
                                                           │ event_key         │
                                                           │ state             │
                                                           │ payload           │
                                                           │ ...               │
                                                           └─────────┬─────────┘
                                                                     │ (1:N)
                                                                     │
                             ┌──────────────┐
                             │ Observation  │
                             ├──────────────┤
                             │ id (PK)      │
                             │ watch_id(FK) │
                             │ provider     │
                             │ observed_at  │
                             │ normalized_data│
                             │ raw_data     │
                             │ content_hash │
                             │ status       │
                             │ error_code   │
                             │ ...          │
                             └──────────────┘
```

## Notification Flow

```
                    AlertEvent
                       │
                       │ (1:N)
                       │
                 ┌─────┴─────┐
                 │ Notification│
                 ├─────────────┤
                 │ id (PK)     │
                 │ user_id(FK) │
                 │ watch_id(FK)│
                 │ alert_event_id(FK)│
                 │ channel     │
                 │ recipient   │
                 │ template    │
                 │ payload     │
                 │ status      │
                 │ ...         │
                 └─────────────┘
                        ▲
                        │
                    (N:1)
                        │
                     User
```

## User Watch Dashboard Query Pattern

```
User
├── Watches (status = 'active')
    ├── Workflows (status = 'active')
    ├── Observations (latest)
    │   └── AlertEvents
    │       └── Notifications
    └── Product
```

## Alert Event Flow

```
Watch (scheduler)
    │
    ▼
Observation (fetched from provider)
    │
    ▼
Workflow Evaluation
    │
    ├── Condition Met? ──► AlertEvent (state: condition_met)
    │       │
    │       ├── Deduplication Check
    │       │   ├── Same event_key exists?
    │       │   ├── Cooldown active?
    │       │   └── Max per day reached?
    │       │
    │       └── Notification Dispatch
    │           │
    │           ▼
    │       Notification (status: pending → sent/failed)
    │
    └── Condition Not Met?
        └── AlertEvent (state: condition_not_met)
```

## Database Tables

### users
```
Column           | Type        | Constraints
─────────────────┼─────────────┼────────────────────────────────
id              | ULID        | PRIMARY KEY
name            | VARCHAR(255)| NOT NULL
mobile          | VARCHAR(20) | NOT NULL, UNIQUE
email           | VARCHAR(255)| NULL
password        | VARCHAR(255)| NOT NULL
status          | VARCHAR(20) | NOT NULL DEFAULT 'active'
mobile_verified_at| TIMESTAMP | NULL
remember_token  | VARCHAR(100)| NULL
created_at      | TIMESTAMP   | NOT NULL
updated_at      | TIMESTAMP   | NOT NULL
deleted_at    | TIMESTAMP   | NULL (soft delete)

Indexes:
- mobile (unique)
- status
```

### products
```
Column           | Type        | Constraints
─────────────────┼─────────────┼────────────────────────────────
id              | ULID        | PRIMARY KEY
provider        | VARCHAR(50) | NOT NULL
external_id     | VARCHAR(255)| NOT NULL
canonical_url   | TEXT        | NOT NULL
title           | TEXT        | NULL
image_url       | TEXT        | NULL
metadata        | JSONB       | NULL
last_known_status| VARCHAR(50)| NOT NULL
created_at      | TIMESTAMP   | NOT NULL
updated_at      | TIMESTAMP   | NOT NULL

Indexes:
- provider, external_id (unique)
- canonical_url (unique)
```

### watches
```
Column           | Type        | Constraints
─────────────────┼─────────────┼────────────────────────────────
id              | ULID        | PRIMARY KEY
user_id         | ULID        | NOT NULL, FK → users.id
product_id      | ULID        | NULL, FK → products.id
name            | VARCHAR(255)| NOT NULL
status          | VARCHAR(20) | NOT NULL DEFAULT 'active'
check_interval_seconds| INTEGER| NOT NULL DEFAULT 900
next_check_at   | TIMESTAMP   | NOT NULL
last_checked_at | TIMESTAMP   | NULL
last_success_at | TIMESTAMP   | NULL
last_error_at   | TIMESTAMP   | NULL
last_error_code | VARCHAR(100)| NULL
created_at      | TIMESTAMP   | NOT NULL
updated_at      | TIMESTAMP   | NOT NULL
deleted_at    | TIMESTAMP   | NULL (soft delete)

Indexes:
- user_id, status (composite)
- status, next_check_at (composite)
- product_id
- next_check_at
```

### workflows
```
Column           | Type        | Constraints
─────────────────┼─────────────┼────────────────────────────────
id              | ULID        | PRIMARY KEY
watch_id        | ULID        | NOT NULL, FK → watches.id
type            | VARCHAR(50) | NOT NULL
version         | INTEGER     | NOT NULL DEFAULT 1
status          | VARCHAR(20) | NOT NULL DEFAULT 'active'
configuration   | JSONB       | NOT NULL
created_at      | TIMESTAMP   | NOT NULL
updated_at      | TIMESTAMP   | NOT NULL

Indexes:
- watch_id, type (composite)
- watch_id, status (composite)
- type, status
```

### observations
```
Column           | Type        | Constraints
─────────────────┼─────────────┼────────────────────────────────
id              | ULID        | PRIMARY KEY
watch_id        | ULID        | NOT NULL, FK → watches.id
provider        | VARCHAR(50) | NOT NULL
observed_at     | TIMESTAMP   | NOT NULL
normalized_data | JSONB       | NOT NULL
raw_data        | JSONB       | NULL
content_hash    | VARCHAR(64) | NOT NULL (SHA256)
status          | VARCHAR(20) | NOT NULL
error_code      | VARCHAR(100)| NULL
error_message   | TEXT        | NULL
response_time_ms| INTEGER     | NULL
created_at      | TIMESTAMP   | NOT NULL

Indexes:
- watch_id, observed_at (composite, descending)
- watch_id, content_hash
- provider, observed_at
- status
```

### alert_events
```
Column           | Type        | Constraints
─────────────────┼─────────────┼────────────────────────────────
id              | ULID        | PRIMARY KEY
watch_id        | ULID        | NOT NULL, FK → watches.id
workflow_id     | ULID        | NOT NULL, FK → workflows.id
observation_id  | ULID        | NOT NULL, FK → observations.id
event_key       | VARCHAR(64) | NOT NULL
state           | VARCHAR(30) | NOT NULL
triggered_at    | TIMESTAMP   | NOT NULL
resolved_at     | TIMESTAMP   | NULL
payload         | JSONB       | NOT NULL
created_at      | TIMESTAMP   | NOT NULL
updated_at      | TIMESTAMP   | NOT NULL

Unique Constraints:
- event_key (unique constraint for deduplication)

Indexes:
- watch_id, event_key
- watch_id, state
- triggered_at DESC
```

### notifications
```
Column           | Type        | Constraints
─────────────────┼─────────────┼────────────────────────────────
id              | ULID        | PRIMARY KEY
user_id         | ULID        | NOT NULL, FK → users.id
watch_id        | ULID        | NOT NULL, FK → watches.id
alert_event_id  | ULID        | NULL, FK → alert_events.id
channel         | VARCHAR(20) | NOT NULL
recipient       | VARCHAR(255)| NOT NULL
template        | VARCHAR(50) | NOT NULL
payload         | JSONB       | NOT NULL
status          | VARCHAR(20) | NOT NULL DEFAULT 'pending'
provider_message_id| VARCHAR(255)| NULL
sent_at         | TIMESTAMP   | NULL
failed_at       | TIMESTAMP   | NULL
failure_reason  | TEXT        | NULL
attempts        | INTEGER     | NOT NULL DEFAULT 1
created_at      | TIMESTAMP   | NOT NULL
updated_at      | TIMESTAMP   | NOT NULL

Indexes:
- user_id, created_at (composite)
- user_id, status
- watch_id, channel
- status, created_at
- alert_event_id
```

### provider_request_logs
```
Column           | Type        | Constraints
─────────────────┼─────────────┼────────────────────────────────
id              | ULID        | PRIMARY KEY
provider        | VARCHAR(50) | NOT NULL
request_hash    | VARCHAR(64) | NOT NULL
endpoint_identifier| VARCHAR(255)| NOT NULL
status_code     | INTEGER     | NULL
response_time_ms| INTEGER     | NULL
success         | BOOLEAN     | NOT NULL
error_code      | VARCHAR(100)| NULL
metadata        | JSONB       | NULL
created_at      | TIMESTAMP   | NOT NULL

Indexes:
- provider, created_at (composite)
- provider, success (composite)
- request_hash
- created_at
```

## View Definitions (Optional)

### user_dash_board_view
```sql
CREATE MATERIALIZED VIEW user_dash_board_view AS
SELECT 
    u.id,
    u.name,
    COUNT(w.id) as total_watches,
    COUNT(CASE WHEN w.status = 'active' THEN 1 END) as active_watches,
    COUNT(ae.id) as total_alerts,
    COUNT(CASE WHEN ae.state = 'notification_sent' THEN 1 END) as notifications_sent,
    COUNT(CASE WHEN ae.state = 'notification_failed' THEN 1 END) as notifications_failed,
    MAX(w.next_check_at) as next_scheduled_check
FROM users u
LEFT JOIN watches w ON w.user_id = u.id
LEFT JOIN workflows wf ON wf.watch_id = w.id
LEFT JOIN alert_events ae ON ae.workflow_id = wf.id
GROUP BY u.id, u.name;
```

### provider_health_view
```sql
CREATE MATERIALIZED VIEW provider_health_view AS
SELECT 
    provider,
    COUNT(*) as total_requests,
    COUNT(CASE WHEN success THEN 1 END) as successful_requests,
    COUNT(CASE WHEN NOT success THEN 1 END) as failed_requests,
    AVG(response_time_ms) as avg_response_time_ms,
    MAX(created_at) as last_request_at,
    COUNT(CASE WHEN status_code = 429 THEN 1 END) as rate_limited,
    COUNT(CASE WHEN status_code >= 500 THEN 1 END) as server_errors,
    COUNT(CASE WHEN status_code = 403 THEN 1 END) as forbidden
FROM provider_request_logs
WHERE created_at > NOW() - INTERVAL '24 hours'
GROUP BY provider;
```

## Migration Strategy

### Initial Migration
1. Create all tables with constraints
2. Add indexes
3. Create enums if PostgreSQL
4. Set up triggers for soft delete (if needed)
5. Add initial seed data

### Subsequent Migrations
- Add columns/constraints as features are implemented
- Create new indexes for performance
- Add partitioning for observations table (by month)
- Archive old notifications/alert_events

## Data Volume Projections

### MVP Scale Estimates (Monthly)
- Users: 1,000
- Products: 10,000 (cached from Torob)
- Active Watches: 2,000
- Observations: 50,000 (every 15min avg)
- AlertEvents: 5,000 (triggered condition)
- Notifications: 1,000 (SMS sent)
- ProviderRequestLogs: 150,000 (scheduling overhead)

### Growth Considerations
- Observations table will grow fastest
- Consider partitioning by observed_at
- Archive old raw_data
- Monitor query performance
- Plan for read replicas

## ER Diagram Visual Summary

```
┌─────────┐
│  User   │
├─────────┤
│ id (PK) │
│ name    │
│ mobile  │
│ ...     │
└─┬───────┘
  │ 1
  │ N
  │
  │    ┌─────────┐
  └────► Watch ◄┼────────┐
       ├─────────┤        │
       │ id(PK)  │        │
       │ user_id │        │
       │ product_│        │
       │ id      │        │
       │ name    │        │
       │ status  │        │
       │ ...     │        │
       └─┬───────┘        │
         │ 1            │ N
         │ N            │
         │              │
  ┌──────▼──────┐       │
  │  Product    │       │
  ├─────────────┤       │
  │ id (PK)     │       │
  │ provider    │◄──────┘
  │ external_id │ (N:1)
  │ canonical_  │
  │ url         │
  │ ...         │
  └─────────────┘
  
  Watch 1 ── N ──► Workflow 1 ── N ──► AlertEvent 1 ── N ──► Notification
       │                    │                       │
       │                    │                       │
       │                    │                       │
       └────────────────────┼───────────────────────┘
                          │ N
                          │
                   ┌──────▼──────┐
                   │Observation  │
                   ├─────────────┤
                   │ id (PK)     │
                   │ watch_id    │
                   │ provider    │
                   │ observed_   │
                   │ at          │
                   │ ...         │
                   └─────────────┘

  User 1 ── N ──► Notification (via alert_event)
  Watch 1 ── N ──► Observation
```