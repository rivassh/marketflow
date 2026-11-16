# Public Rule: TODO Compliance
# Every agent, developer, devops, llmops must respect tasks listed in @TODO.md.
# - Move completed tasks to the "in_progress" section after marking them "done"
# - New tasks must be added to the TODO.md file before work begins
# - All tasks must be tracked in this file; no work outside of TODO.md is permitted

every time take a todo task mve it to in_progress part after done move to done part.

# IN_PROGRESS

# COMPLETED
* Reviewed project status and production readiness
* Created main branch and merged current work
* Updated Nuxt dependency from 3.12.1 to 3.21.10 (dependabot)
* Fixed production configuration in .env.example
* Confirmed deployment at torob.adlr.ir is operational
* Phase 0: Stabilization blockers — all 8 items complete
* Phase 1: Provider Layer (Torob + Divar) — all items complete
* Phase 2: Purchase Domain (PurchaseIntent, PurchaseOrder, PurchaseItem) — all items complete

# PHASE 0 — Stabilization (blockers) — DONE
- [x] 1. Fixed NotificationService::send() static/instance mismatch in WorkflowDispatcher and WorkflowAction
- [x] 2. Unified ChannelRegistry import path (App\Services\ChannelRegistry)
- [x] 3. Added next_check_at column to watches migration + index on (is_active, is_tracked, next_check_at)
- [x] 4. Fixed .gitignore to include .env, vendor/, node_modules/, .nuxt/, storage/*.log
- [x] 5. Removed mimocodes symlink, backend/debug_test.php, prompt0.txt
- [x] 6. Fixed Makefile (removed phpstan/lint references, kept pint + npm scripts)
- [x] 7. Fixed frontend: removed duplicate stores/auth.ts (moved to stores/), created pages/watches.vue
- [x] 8. Added LICENSE (MIT) + README.md with architecture, run instructions, feature status

# PHASE 1 — Provider Layer (Torob + Divar) — DONE
- [x] MarketplaceProvider contract + binding in AppServiceProvider
- [x] Real ProviderRegistry reading from provider_registry table
- [x] TorobProcessor: keep slug, use real endpoint, HTML scraping with Accept: text/html, config selectors
- [x] DivarProcessor: support divar.ir/v/<slug>/<token>, normalized fields, rate limit/robots configurable
- [x] WatchController::store uses registry instead of hardcoded TorobProcessor
- [x] Tests: supports(), URL parsing, normalize with fixtures (Http::fake)

# PHASE 2 — Purchase Domain — DONE
- [x] Models: PurchaseIntent, PurchaseOrder, PurchaseItem, PurchaseAuditLog
- [x] State machine: draft → awaiting_approval → approved → placed → failed/cancelled
- [x] No automatic financial transactions; max to awaiting_approval; user approves explicitly
- [x] Endpoints: CRUD purchase-intents + submit + CRUD purchase-orders + submit + approve
- [x] Audit log for every status change (who, when, what data)
- [x] Feature test for full state cycle including unauthorized approve

# PHASE 3 — SiteAction Execution Layer
- [ ] SiteAction interface: supports(url), plan(context), execute(plan)
- [ ] Playwright Node sidecar execution
- [ ] Human-in-the-loop: plan returned for confirmation before execute
- [ ] Captcha/OTP/payment = stop and escalate to user
- [ ] First action: "Add product to Torob cart" dry-run