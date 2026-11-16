# AGENTS.md

Project: MarketFlow (Laravel 13 + Nuxt 3)

## Commands
- Test: `cd backend && php artisan test`
- Lint/Format: `cd backend && php vendor/bin/pint`
- Migrate: `cd backend && php artisan migrate --force`
- Worker: `cd backend && php artisan queue:work database --timeout=60`
- Scheduler: `cd backend && php artisan schedule:work`

## Project Rules
- DDD/SOLID; no over-engineering — every abstraction needs 2+ consumers
- Every change = code + test. Tests without assertions are not accepted.
- No real network calls in tests — use `Http::fake`.
- No automatic financial transactions — purchase flow stops at `awaiting_approval`.
- Small commits only — one branch + small PR per phase.
- Stop and report at end of each phase.

## Testing Environment
Tests run inside Docker container:
```bash
cd backend && docker build -t marketflow-backend:latest .
docker run --rm -v $(pwd)/backend:/var/www/html -w /var/www/html marketflow-backend:latest php artisan test
```

## Architecture
- Provider Layer: `MarketplaceProvider` contract → `TorobProcessor` / `DivarProcessor`
- Watch Domain: `Watch` → `Observation` → `AlertEvent`
- Workflow: `WorkflowDispatcher` (injected) + `WorkflowConditionEvaluator`
- Purchase Domain (Phase 2): `PurchaseIntent` → `PurchaseOrder` → `PurchaseItem`
- SiteAction (Phase 3): `supports(url)` → `plan(context)` → `execute(plan)`