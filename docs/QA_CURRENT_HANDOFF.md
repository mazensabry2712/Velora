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
SHA: 3830c4178650694b1b730f570002f6e0a5efc95d
```

The current checkpoint includes:

```text
- MySQL as the active application/test database contract
- historical SQLite diagnostics explicitly marked as historical
- Billing portal central-connection hardening
- SubscriptionService central-connection hardening
- subscription limit middleware central-connection hardening
- subscription lifecycle command central-connection hardening
- subscription reminder command central-connection hardening
- billing reader central-connection hardening
- upgrade-request and legacy subscription-access central-connection hardening
- system-notification central-connection hardening
- global SystemSetting and CountryPricing models pinned to the canonical central DB
- PaymentGatewayRouter contract aligned with PaymentGatewayManager-supported gateways
- payment gateway cache invalidation regression coverage
- central-model connection regression coverage
- Stripe subscription webhook idempotency correction
- invalid/inactive Moyasar plan rejection
- normalized Queue/Mail settings across certification workflows
- SQLite fallback removal from certification test infrastructure
- canonical report ranges using Appointment.starts_at
- Excel appointment export aligned with the shared report range resolver
- report period/custom-range input validation
- report/export canonical range regression coverage
- tenant-aware image storage through the Laravel filesystem
- tenant image storage regression coverage
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

## Current billing boundary contract

All central billing/subscription reads and writes must resolve the canonical central connection from `tenancy.database.central_connection` rather than hard-coding `mysql` in call sites.

This contract is now applied in:

```text
BillingController
SubscriptionService
CheckSubscriptionLimits
CheckSubscriptionStatus
SendSubscriptionLifecycleReminders
EloquentBillingReader
LegacyBillingReader
EloquentSubscriptionReader
EloquentSubscriptionAccessReader
LegacySubscriptionAccessReader
EloquentUpgradeRequestWriter
EloquentTrialExtender
SubscriptionPlan
TenantSubscription
```

Global billing/pricing configuration is also explicitly central:

```text
SystemSetting
CountryPricing
```

Webhook authority/idempotency contract:

```text
Stripe and Moyasar browser callbacks do not activate subscriptions.
Webhook handlers verify provider authenticity before state mutation.
Webhook events are deduplicated by provider event identity.
Stripe subscription updates are not rejected merely because the same
Stripe subscription ID was seen previously.
```

## Reporting / export contract

```text
Appointment date/time source of truth = starts_at / ends_at.
ReportService, dashboard reporting, and Excel appointment export use starts_at.
Report periods are centralized in ReportService::resolveRange().
Custom report/export ranges require valid start_date/end_date input.
```

Regression guards:

```text
tests/Feature/QA/ReportingCanonicalRangeScenarioTest.php
tests/Feature/QA/ReportingReconciliationScenarioTest.php
```

## Storage contract

```text
New tenant images use Laravel's public filesystem disk.
Storage paths are handled by the tenancy filesystem bootstrapper.
Legacy image records retain compatibility fallbacks for historical files.
```

Regression guard:

```text
tests/Feature/QA/TenantImageStorageScenarioTest.php
```

## Completed hardening / coverage on the main line

```text
Public booking golden flow
Public booking per-tenant/IP rate limiting
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
Canonical reporting date-range reconciliation
Service/Staff/Settings authorization hardening
Expanded authorization matrix
Onboarding mutation authorization
Stripe central-connection hardening
Stripe subscription event idempotency correction
Holiday calendar-date comparison
Dashboard daily appointment date reconciliation
PHPUnit test environment bootstrap hardening
CI environment alignment with the canonical MySQL contract
Billing/subscription central-connection alignment
Payment gateway router/manager capability alignment
Payment gateway settings cache invalidation coverage
Central model connection contract coverage
Excel appointment export/report-range alignment
Report input validation
Tenant-aware image storage isolation
```

## Historical findings

The repository contains dated reports describing earlier SQLite test-infrastructure failures and schema mismatches. Those documents are historical evidence, not the current environment contract.

Historical items include:

- `QA-TESTINFRA-001` tenant transaction rollback safety
- `QA-TESTINFRA-002` missing physical `.env` bootstrap
- `QA-TESTINFRA-003` SQLite `:memory:` lifecycle failure
- `QA-REPORT-002` SQLite-incompatible reporting query shape
- `QA-BILLING-003` unsupported `billing_cycle` subscription write

The historical findings remain useful as diagnosis records; current PHPUnit/CI certification is MySQL-based.

## Current CI state

Latest pushed checkpoint:

```text
3830c4178650694b1b730f570002f6e0a5efc95d
```

Fresh GitHub Actions runs for this checkpoint were observed in `queued` state at the time of this update. Their final results must be fetched before making any pass/fail or certification claim.

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
docs/QA_FINDING_BILLING_PORTAL_CONNECTION.md ← billing finding
  ↓
docs/QA_RUN_120_POSTMORTEM.md        ← historical checkpoint
  ↓
docs/QA_RUN_LOCAL_SQLITE_2026-09-01.md ← historical SQLite diagnosis only
```
