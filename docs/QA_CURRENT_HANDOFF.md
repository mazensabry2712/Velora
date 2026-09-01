# Velora QA Current Handoff

**Purpose:** Snapshot for the next chat/session. This file is intentionally small and is updated whenever QA execution state changes materially. It must never claim a gate is closed unless the relevant evidence is present.

## Repository

```text
Repository: mazensabry2712/Velora
Branch: main
```

## Current main head

```text
SHA: cb16b9bf6e5b6c6802aa6fc0b1b969fc24fb7943
```

This SHA contains the latest PHPUnit environment-bootstrap remediation and its documentation. Always verify `refs/heads/main` before continuing.

## Method in one line

```text
Inspect current main → check current CI → classify the first confirmed discrepancy → regression test → root-cause diagnosis → minimal fix → focused regression → MySQL Master QA → document finding/status → continue.
```

## Non-negotiable rules

1. All accepted work for this QA workstream goes directly to `main`.
2. Never claim a test passed unless its result was actually observed.
3. Never claim certification while the relevant CI run is queued or in progress.
4. MySQL 8.4 + PHP 8.4 is the certification environment. SQLite is not sufficient evidence for tenant, locking, billing, webhook, concurrency or final certification gates.
5. Every confirmed production defect requires a regression test.
6. Do not weaken assertions to obtain green CI. Change an assertion only when the production contract proves that assertion is wrong.
7. Do not add a package when native Laravel/PHP/project code is sufficient.
8. Reconcile dashboards/reports against canonical database truth.
9. Treat central and tenant databases as separate security/data boundaries.
10. Never overwrite documentation from memory and lose older findings.
11. Distinguish production defects from test-fixture/test-infrastructure defects; fix the smallest correct layer.
12. Never assume a commit mentioned in conversation is on `main`; verify the actual `refs/heads/main` SHA.
13. Keep real `.env` secrets out of Git. Local/CI tests must bootstrap safely without requiring a committed `.env`.

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
PHPUnit temporary .env bootstrap remediation added
PHPUnit environment regression test added

```

## Latest completed Master QA evidence

Run **#120** tested commit `281268faf99337b2c9c62f3c9e679222268f76ee`:

```text
53 passed
6 failed
240 assertions
```

Detailed report:

```text
docs/QA_RUN_120_POSTMORTEM.md
```

Run #120 is historical evidence only. It does not certify the current `main` head.

## Run #120 failure classifications

```text
1. Onboarding Staff/Assistant authorization
   → confirmed production authorization gap
2. Moyasar central connection test
   → missing clean-environment fixture
3. Tenant deletion success assertion
   → central-connection boundary hardening required
4. Tenant resource isolation teardown
   → dynamic tenant connection test-infrastructure defect
5. Tenant token isolation teardown
   → same test-infrastructure defect
6. Duplicate class tenant fixture
   → secondary leak from the teardown defect
```

## Latest local full-suite evidence supplied by the developer

After pulling `main` with `.env` removed, the local `php artisan test` run showed widespread failures/warnings because many legacy tests directly read the physical `.env` file. The failing output included payment gateway tests, repository tests, admin tests, booking/journey tests, localization/geo tests, and health/design-system tests. HTTP tests then cascaded into `MissingAppKeyException`, and Symfony's error renderer eventually hit the PHP 128 MB memory limit while rendering the repeated exception payloads.

The supplied log also shows that `.env` had been deleted by the security cleanup and that the local branch was successfully fast-forwarded to the QA changes. This was classified as **QA-TESTINFRA-002**, not as dozens of independent production defects.

## Remediation for QA-TESTINFRA-002

```text
phpunit.xml
→ bootstrap changed from vendor/autoload.php to tests/bootstrap.php

tests/bootstrap.php
→ if .env exists: preserve it
→ if .env is missing: copy .env.example into a temporary .env
→ force APP_ENV=testing
→ inject a throwaway random APP_KEY
→ force a localhost APP_URL for the temporary environment
→ require Composer autoload
→ remove only the generated .env on process shutdown

tests/Unit/TestEnvironmentBootstrapTest.php
→ verifies physical .env exists during PHPUnit execution
→ verifies APP_ENV=testing
→ verifies a non-empty generated APP_KEY
→ verifies .env remains ignored while .env.example remains allowed
```

Security contract remains intact:

```text
.env             → never committed
.env.*           → ignored
.env.example     → committed template only
```

The `.gitignore` already enforces this contract. The bootstrap is therefore a test-environment compatibility layer, not a return of secrets to source control.

## Current CI requirement

The current certification target is exactly:

```text
cb16b9bf6e5b6c6802aa6fc0b1b969fc24fb7943
```

A fresh Master QA run and broader quality run must match that SHA before their results can be treated as current evidence. Until the relevant runs complete successfully, Velora remains **not certified**.

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

Playwright is already installed and configured. Existing specs:

```text
tests/browser/booking.spec.js
tests/browser/queue.spec.js
playwright.config.js
```

Do not add another browser framework. Existing browser tests include deterministic mocked UI paths plus real availability checks. A browser CI gate must use a deterministic application/tenant bootstrap; a fake gate that can pass without exercising the app is not acceptable.

## Current next gate

```text
Fresh MySQL CI on current main
→ validate QA-TESTINFRA-002 remediation
→ close any remaining Master QA failures
→ Billing ↔ Subscription full reconciliation
→ Full tenant/resource authorization matrix
→ Super Admin financial/revenue reconciliation
→ Reporting/export reconciliation
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
docs/QA_RUN_120_POSTMORTEM.md        ← detailed CI failure/remediation report
```