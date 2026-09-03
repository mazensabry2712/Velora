# Velora QA Current Handoff

**Purpose:** Snapshot for the next chat/session. This file describes the current canonical QA state only. Historical diagnostics remain preserved in the findings log and dated run reports.

## Repository

```text
Repository: mazensabry2712/Velora
Branch: main
Current certification environment: MySQL 8.4 + PHP 8.4
```

Always verify `refs/heads/main` before continuing. Never assume a SHA from an older chat is still current.

## Current canonical checkpoint

```text
SHA: d8e32a015bf837627832dba545dfb484f9318b96
```

The current checkpoint includes:

```text
- MySQL as the active application/test database contract
- historical SQLite diagnostics explicitly marked as historical
- Billing portal central-connection hardening
- normalized Queue/Mail settings across certification workflows
- regression guard for Billing portal connection selection
```

## QA method

```text
Inspect current main
→ verify current CI
→ classify the first confirmed discrepancy
→ add regression coverage
→ minimal root-cause fix
→ focused regression
→ MySQL Master QA
→ reconcile projections with canonical DB truth
→ document status
→ continue
```

## Non-negotiable rules

1. All accepted QA work for this workstream goes directly to `main`.
2. Never claim a test passed unless its result was actually observed.
3. Never claim certification while the relevant CI run is queued or in progress.
4. MySQL 8.4 + PHP 8.4 is the certification environment.
5. Every confirmed production defect requires a regression test.
6. Do not weaken assertions to make CI green.
7. Do not add packages where native Laravel/PHP/project code is sufficient.
8. Reconcile dashboards, reports, exports, and notifications against canonical database truth.
9. Treat central and tenant databases as separate security/data boundaries.
10. Keep historical findings; do not replace them with current-state claims.
11. Distinguish production defects from test-fixture/test-infrastructure defects.
12. Keep real `.env` secrets out of Git.
13. Avoid broad refactors during a failing CI investigation.

## Current environment contract

### Application database

```text
config/database.php
DB_CONNECTION defaults to mysql
Only the mysql application connection is configured
No SQLite application fallback is defined
```

### Tenant central database

```text
config/tenancy.php
central_connection = TENANCY_CENTRAL_CONNECTION → DB_CONNECTION → mysql
manager = MySQLDatabaseManager
```

### PHPUnit

```text
phpunit.xml
DB_CONNECTION and DB_DATABASE are not hard-coded to SQLite
Queue = sync
Mail = array
Cache = array
Session = array
```

### Test bootstrap

```text
tests/bootstrap.php
Uses the local .env when present
Creates a temporary .env only when local .env is absent
Generates a throwaway APP_KEY
Forces APP_ENV=testing
Does not create or switch to a SQLite test database
```

### CI

All certification workflows use MySQL 8.4 + PHP 8.4 and `pdo_mysql`.

`Velora Tests` runs the full PHPUnit suite.
`Velora Master QA` runs `tests/Feature/QA`.
`Velora Quality` runs dependency/security/static-quality checks plus the test suite.

All three explicitly normalize test Queue/Mail behavior to `sync`/`array`.

## Current Billing contract

Tenant billing data that belongs to the central database must resolve the configured central connection through `tenancy.database.central_connection` rather than hard-coding `mysql` at the call site.

`BillingController::portal()` now follows the same contract as the checkout path and hardened billing services.

Regression guard:

```text
tests/Unit/BillingCentralConnectionContractTest.php
```

Finding record:

```text
docs/QA_FINDING_BILLING_PORTAL_CONNECTION.md
```

## Completed hardening / coverage on the main line

```text
Public booking golden flow
Booking rules and schema regressions
Appointment lifecycle
Queue lifecycle and business-date correctness
Call-next locking/date scoping
Customer/dashboard reconciliation
Notification delivery/recovery basics
Moyasar webhook fail-closed authentication
Moyasar payment verification/retry scenarios
Moyasar central-connection activation
Tenant transaction connection safety
Tenant token/resource isolation
Super Admin tenant/subscription reconciliation
Reporting customer-source reconciliation
Tenant deletion safety
Service/Staff/Settings authorization hardening
Expanded authorization matrix
Onboarding mutation authorization
Stripe central-connection hardening
Holiday calendar-date comparison
Dashboard daily appointment date reconciliation
PHPUnit test environment bootstrap hardening
CI environment alignment with the canonical MySQL contract
Billing portal central-connection hardening
```

## Important historical findings

The repository contains dated reports describing earlier SQLite test-infrastructure failures. Those documents are historical evidence, not the current environment contract.

Historical items include:

- `QA-TESTINFRA-001` tenant transaction rollback safety
- `QA-TESTINFRA-002` missing physical `.env` bootstrap
- `QA-TESTINFRA-003` SQLite `:memory:` lifecycle failure
- `QA-REPORT-002` SQLite-incompatible reporting query shape
- `QA-BILLING-003` unsupported `billing_cycle` subscription write

The historical SQLite findings remain useful as diagnosis records, but current PHPUnit/CI certification is MySQL-based.

## Current CI state

Latest pushed checkpoint:

```text
d8e32a015bf837627832dba545dfb484f9318b96
```

Fresh GitHub Actions runs have been triggered by the recent `main` updates. Their final result must be fetched before making a pass/fail or certification claim.

## Current release gate

```text
Current main
→ fresh MySQL Master QA
→ broader quality/full regression
→ Billing ↔ Subscription full reconciliation
→ full tenant/resource authorization matrix
→ Super Admin financial/revenue reconciliation
→ Reports / export reconciliation
→ deletion / storage / DB cleanup certification
→ deterministic Playwright journeys
→ final production go/no-go
```

## Documentation map

```text
README.md
  ↓
docs/MASTER_QA_EXECUTION_RUNBOOK.md   ← QA execution rules
  ↓
docs/QA_CURRENT_HANDOFF.md           ← current state
  ↓
docs/QA_FINDINGS_LOG.md              ← permanent finding history
  ↓
docs/QA_FINDING_BILLING_PORTAL_CONNECTION.md ← latest billing finding
  ↓
docs/QA_RUN_120_POSTMORTEM.md        ← historical checkpoint
  ↓
docs/QA_RUN_LOCAL_SQLITE_2026-09-01.md ← historical SQLite diagnosis only
```
