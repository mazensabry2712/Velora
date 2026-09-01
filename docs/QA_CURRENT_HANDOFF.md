# Velora QA Current Handoff

**Purpose:** Snapshot for the next chat/session. This file is intentionally small and is updated whenever QA execution state changes materially. It must never claim a gate is closed unless the relevant evidence is present.

## Repository

```text
Repository: mazensabry2712/Velora
Branch: main
```

## Current main head

```text
SHA: e2473f3665f43d3c3d89ff6ffcd3beed1da2e104
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
Holiday calendar-date comparison fix
Dashboard daily appointment date reconciliation fix

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

Before the canonical-main reset, the developer ran the booking/dashboard regression batch and observed:

```text
40 passed
2 failed
163 assertions
```

The two failures were:

```text
BookingAvailabilityRulesScenarioTest
→ Holiday row existed but SlotEngine did not recognize it

MasterBusinessFlowScenarioTest
→ Dashboard confirmed count was 0 while canonical database truth was 1
```

After resetting the local checkout to canonical `origin/main` and reinstalling dependencies, both failures reproduced on the canonical test code.

## Latest findings added

### QA-BOOK-002 — Holiday availability comparison was too strict for a calendar-date field

`BookingAvailabilityRulesScenarioTest::holiday_makes_the_staff_unavailable_even_when_working_hours_exist()` persisted an all-staff holiday successfully, but `SlotEngine::validateSlot()` returned an available result for the same calendar day.

The test then isolated the layer: the Holiday fixture existed, while the domain `SlotEngine` result was still available. This proved the defect was in Holiday lookup semantics, before `CreatePublicBooking` could translate the domain result into `SlotUnavailableException`.

Root cause:

```text
Holiday model stores a calendar date
→ SlotEngine queried `where('date', $date->toDateString())`
→ stored representation could include a time component
→ exact equality missed the same calendar day
```

Remediation:

```text
SlotEngine::isHoliday()
→ `whereDate('date', $date->toDateString())`
```

The business contract is unchanged: a holiday matching the staff's local calendar date blocks booking. The regression already present in `BookingAvailabilityRulesScenarioTest` verifies fixture persistence, direct SlotEngine rejection with reason `holiday`, and higher-level booking rejection.

Fix commit: `cfc9e468a3b65c13d3c11ec7aec0c6381a555cc2`.

### QA-REPORT-003 — Tenant Dashboard daily appointment metrics disagreed with canonical database truth

`MasterBusinessFlowScenarioTest::dashboard_reconciles_exactly_with_database_truth_for_the_golden_dataset()` created one confirmed appointment on the business date. The canonical `whereDate('date', $today)` query returned `1`, while Dashboard `stats['confirmed']` returned `0`.

Root cause:

```text
Dashboard aggregate
→ exact `date = ?` comparison
→ possible mismatch when date representation contains a time component
→ projected confirmed count diverged from canonical `whereDate()` truth
```

Remediation:

```text
Dashboard aggregate
→ `DATE(date) = ?` for total_today / completed_today / confirmed_today
```

The aggregate structure and metric meanings remain unchanged; only calendar-date matching was normalized. The existing master scenario is the regression guard and reconciles the projection with direct database truth.

Fix commit: `c033fb8fd4628dad7cda5e569ccc7073500b27bf`.

## Current canonical-main checkpoint

The latest documentation commit after both fixes is:

```text
SHA: e2473f3665f43d3c3d89ff6ffcd3beed1da2e104
```

This SHA includes:

```text
cfc9e468  fix(booking): compare holidays by calendar date
c033fb8f  fix(dashboard): reconcile daily appointment date comparisons
e2473f36  docs(qa): record booking availability and dashboard findings
```

Fresh CI evidence must be fetched for this current head. Local `vendor/` is intentionally untracked and must not be added to Git.

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

Booking holiday regression:

```text
php artisan test tests/Feature/QA/BookingAvailabilityRulesScenarioTest.php --filter=holiday_makes_the_staff_unavailable_even_when_working_hours_exist --compact
```

Dashboard reconciliation regression:

```text
php artisan test tests/Feature/QA/MasterBusinessFlowScenarioTest.php --compact
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
e2473f3665f43d3c3d89ff6ffcd3beed1da2e104
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
→ verify holiday enforcement regression
→ verify dashboard date reconciliation regression
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
