# Velora QA Current Handoff

**Purpose:** Snapshot for the next chat/session. This file is intentionally small and is updated whenever QA execution state changes materially. It must never claim a gate is closed unless the relevant evidence is present.

## Repository

```text
Repository: mazensabry2712/Velora
Branch: main
```

## Current main head

```text
SHA: 1457212c9816c14e4e01ec408ce5e10c1e5d371f
```

Always verify `refs/heads/main` before continuing. Never assume a SHA from an older chat is still the current main.

## Method in one line

```text
Inspect current main → check current CI → classify the first confirmed discrepancy → regression test → root-cause diagnosis → minimal fix → focused regression → MySQL Master QA → document finding/status → continue.
```

## Non-negotiable rules

1. All accepted work for this QA workstream goes directly to `main`.
2. Never claim a test passed unless its result was actually observed.
3. Never claim certification while the relevant CI run is queued or in progress.
4. MySQL 8.4 + PHP 8.4 is the certification environment. SQLite is useful for fast local feedback but is not sufficient evidence for final certification of tenant, locking, billing, webhook, concurrency, or production gates.
5. Every confirmed production defect requires a regression test.
6. Do not weaken assertions to obtain green CI. Change an assertion only when the production contract proves that assertion is wrong.
7. Do not add a package when native Laravel/PHP/project code is sufficient.
8. Reconcile dashboards/reports against canonical database truth.
9. Treat central and tenant databases as separate security/data boundaries.
10. Never overwrite documentation from memory and lose older findings.
11. Distinguish production defects from test-fixture/test-infrastructure defects; fix the smallest correct layer.
12. Never assume a commit mentioned in conversation is on `main`; verify the actual `refs/heads/main` SHA.
13. Keep real `.env` secrets out of Git. Local/CI tests must bootstrap safely without requiring a committed `.env`.
14. Do not run broad refactors during a failing CI investigation. Fix the first confirmed root cause and add its regression guard.

## What the QA program is proving

The target is system certification, not a large test count.

```text
UI / HTTP
  ↓
Application / Domain
  ↓
Database state
  ↓
Events / Jobs / Notifications
  ↓
Dependent projections/readers
  ↓
Tenant Dashboard / Reports / Super Admin
  ↓
Business invariants
  ↓
Security / authorization / concurrency
```

A scenario is accepted only when the same business fact remains consistent everywhere that depends on it.

## Completed coverage / hardening implemented on the main line

```text
Environment foundation
Public booking golden flow
Booking rules / schema regressions
Appointment lifecycle
Queue lifecycle
Queue business-date correctness
Call-next locking/date scoping
Customer/dashboard reconciliation
Notification lifecycle/recovery basics
Moyasar webhook fail-closed authentication
Moyasar payment verification/retry scenarios
Moyasar central-connection activation
Tenant test transaction connection safety
Tenant token/resource isolation tests
Super Admin tenant/subscription reconciliation tests
Reporting customer-source reconciliation
Tenant deletion safety tests
Service/Staff/Settings authorization hardening
Expanded authorization tests
Onboarding mutation authorization hardening
Stripe central-connection hardening
QA suite cleanup: temporary marker/placeholder files removed
PHPUnit temporary .env bootstrap remediation
Process-isolated SQLite test database bootstrap
Central schema bootstrap for fresh test applications
SQLite-compatible staff reporting query
Self-contained Super Admin billing/deletion fixtures

```

## Historical Master QA evidence

Run #120 tested commit `281268faf99337b2c9c62f3c9e679222268f76ee`:

```text
53 passed
6 failed
240 assertions
```

This run is historical diagnostic evidence only.
Detailed report:

```text
docs/QA_RUN_120_POSTMORTEM.md
```

## Latest local QA evidence supplied by the developer

The developer ran:

```text
php artisan test tests/Feature/QA --compact
```

and observed:

```text
46 failed
13 passed
72 assertions
21.53s
```

The dominant failure signatures were:

```text
no such table: tenants
no such table: subscription_plans
```

The failures came from many unrelated test classes and occurred during fixture initialization, which identified a shared SQLite/test-bootstrap defect rather than 46 independent product defects.

One additional product/query compatibility failure was observed:

```text
ReportService::getStaffPerformance()
→ SQLite: HAVING clause on a non-aggregate query
```

One billing schema-contract mismatch was observed:

```text
MoyasarService::activateSubscription()
→ no such column: billing_cycle
```

## Latest findings added

### QA-TESTINFRA-003 — SQLite `:memory:` lifecycle

`phpunit.xml` used `DB_DATABASE=:memory:`. `TenantTestCase` cached migration completion per test class, but Laravel can boot a fresh application/connection while the static cache survives. A new in-memory SQLite connection therefore had no `tenants` or other central tables.

Remediation:

```text
SQLite test DB
→ process-isolated file
→ TEST_TOKEN / PARALLEL_PROCESS / PID naming
→ cleaned on shutdown
```

This also removes the shared-memory conflict that caused the earlier parallel run to explode into hundreds of `no such table` errors.

### QA-REPORT-002 — SQLite-incompatible staff-performance query

`ReportService` used `HAVING` on a `withCount()` alias. This was replaced by a `whereHas()` existence filter while retaining `withCount()` for the returned count.

### QA-BILLING-003 — unsupported subscription `billing_cycle` write

`SubscriptionPlan` is the canonical source of `billing_cycle`. `tenant_subscriptions` does not define that column. The duplicate write was removed from `MoyasarService`.

## Current Test Environment Contract

`tests/bootstrap.php` now:

```text
Creates temporary .env only when local .env is missing
Generates throwaway APP_KEY
Forces APP_ENV=testing and localhost APP_URL
Creates one SQLite file per PHPUnit process
Uses TEST_TOKEN / PARALLEL_PROCESS / PID to isolate workers
Deletes generated .env and test DB on shutdown
```

`.env` remains excluded from Git. Test SQLite files are also ignored by `.gitignore`:

```text
database/testing_*.sqlite
```

`tests/TestCase.php` ensures the configured central connection has been migrated when a fresh test application starts.

`tests/Unit/TestEnvironmentBootstrapTest.php` guards this contract.

## Current local test commands

Fast targeted regression:

```text
php artisan test tests/Unit/TestEnvironmentBootstrapTest.php
```

Master QA local suite:

```text
php artisan test tests/Feature/QA --compact
```

Parallel fast regression after process-isolated SQLite remediation:

```text
php artisan test --parallel --processes=12
```

Full sequential regression:

```text
php artisan test
```

Parallel is a speed check, not final certification. It must use isolated databases and still be followed by the canonical MySQL CI gates.

## Current CI requirement

The current certification target is exactly:

```text
1457212c9816c14e4e01ec408ce5e10c1e5d371f
```

Fresh CI evidence must match this or a later current `main` SHA. Until the relevant Master QA and broader quality runs complete successfully, Velora remains **not certified**.

Canonical Master QA:

```text
.github/workflows/master-qa.yml
DB_CONNECTION=mysql
TENANCY_CENTRAL_CONNECTION=mysql
MySQL 8.4
PHP 8.4
php artisan migrate --force
php artisan test tests/Feature/QA --compact
```

## E2E status

Playwright is already installed and configured.

```text
tests/browser/booking.spec.js
tests/browser/queue.spec.js
playwright.config.js
```

Do not add another browser framework. Before creating a CI browser gate, establish deterministic tenant/application bootstrap so the browser tests exercise a real running application rather than a fake passing path.

## Current next gate

```text
Fresh MySQL CI on current main
→ close remaining Master QA failures
→ Billing ↔ Subscription full reconciliation
→ Full tenant/resource authorization matrix
→ Super Admin financial/revenue reconciliation
→ Reports / Excel export reconciliation
→ Deletion / storage / DB cleanup certification
→ Deterministic Playwright browser journeys
→ Full regression
→ Production go/no-go certification
```

## Documentation map

```text
README.md
  ↓
docs/MASTER_QA_EXECUTION_RUNBOOK.md   ← how to work
  ↓
docs/QA_CURRENT_HANDOFF.md           ← where to continue
  ↓
docs/QA_FINDINGS_LOG.md              ← long-term finding history
  ↓
docs/QA_RUN_120_POSTMORTEM.md        ← historical diagnostic checkpoint
  ↓
docs/QA_RUN_LOCAL_SQLITE_2026-09-01.md ← latest local SQLite diagnosis/remediation
```
