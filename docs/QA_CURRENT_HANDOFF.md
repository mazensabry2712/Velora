# Velora QA Current Handoff

**Purpose:** Snapshot for the next chat/session. This file is intentionally small and is updated whenever QA execution state changes materially. It must never claim a gate is closed unless the relevant evidence is present.

## Repository

```text
Repository: mazensabry2712/Velora
Branch: main
```

## Current main head

```text
SHA: c4e397232ac439bdd6caae8ea5832621e2486248
```

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
Tenant test transaction connection safety (fix in main; fresh CI pending)
Tenant token/resource isolation tests (tests in main; fresh CI pending)
Super Admin tenant/subscription reconciliation tests
Reporting customer-source reconciliation
Tenant deletion safety tests
Service/Staff/Settings authorization hardening
Expanded authorization tests
Onboarding mutation authorization hardening (production fix now in main; fresh CI pending)
Stripe central-connection hardening (fix in main; fresh CI pending)

```

## Latest observed MySQL Master QA evidence

The authoritative recent Master QA run was **Run #120** on commit:

```text
281268faf99337b2c9c62f3c9e679222268f76ee
```

Result:

```text
53 passed
6 failed
240 assertions
```

Important: this run is evidence for commit `281268f`, not for newer commits. The failures were:

```text
1. AuthorizationMatrixExpandedScenarioTest
   Staff/Assistant onboarding mutation expected 403, received 200.
   Classification: confirmed production authorization gap.

2. MoyasarCentralConnectionScenarioTest
   No SubscriptionPlan fixture existed in a clean central DB.
   Classification: QA fixture issue, not a Moyasar business failure.

3. TenantDeletionSafetyScenarioTest
   Successful cleanup assertion still found the subscription.
   Classification: requires production/connection-path verification.

4. TenantIsolationResourceScenarioTest
   Dynamic connection [tenant] no longer existed during test teardown.
   Classification: test infrastructure / tenancy teardown issue.

5. TenantIsolationSecurityScenarioTest
   Dynamic connection [tenant] no longer existed during test teardown.
   Classification: test infrastructure / tenancy teardown issue; same root family as #4.

6. Shared tenant fixture duplication
   A later tenant test attempted to recreate `test-tenant-*` already left behind.
   Classification: test infrastructure leakage caused by the teardown problem.
```

## Fixes added after Run #120

```text
TenantTestCase
→ captures the concrete tenant + central Connection objects
→ rolls back using those objects before dynamic tenancy is ended
→ avoids reopening a deleted `tenant` connection during tearDown

OnboardingController
→ Admin Tenant guard added to saveStep1/saveStep2/saveStep3/complete

MoyasarCentralConnectionScenarioTest
→ creates its own valid SubscriptionPlan fixture instead of assuming a seeder ran

PermanentlyDeleteExpiredTenants
→ resolves Tenant records explicitly through the configured central connection
```

These changes are now on `main`, but they are **not certified yet**. The next CI run must match the current `main` SHA `c4e397232ac439bdd6caae8ea5832621e2486248`.

## CI configuration

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

The general quality workflow also exists, but its full PHPUnit step currently uses SQLite for speed. It is useful for broad quality checks, but it is not enough by itself for tenant/locking/billing/concurrency certification.

## Current next gate

```text
Fresh MySQL CI on current main
→ close/verify the six Run #120 failures
→ Billing ↔ Subscription full reconciliation
→ Full tenant/resource authorization matrix
→ Super Admin financial/revenue reconciliation
→ Reports / Excel export reconciliation
→ Deletion / storage / DB cleanup certification
→ Playwright browser journeys
→ Full regression
→ Production go/no-go certification
```

## E2E status

Playwright is already installed and configured in the repository; do not add another browser framework.

Existing browser specs:

```text
tests/browser/booking.spec.js
tests/browser/queue.spec.js
playwright.config.js
```

Current Playwright configuration supports:

```text
PLAYWRIGHT_BASE_URL override
Chromium desktop
Chromium mobile
trace on failure
screenshots on failure
video on failure
```

The existing booking suite contains deterministic mocked scenarios as well as real availability checks. A full browser CI gate should be added only after the test environment has a deterministic tenant/database bootstrap; do not create a fake browser gate that can pass without exercising the application.

## Documentation map

```text
README.md
  ↓
docs/MASTER_QA_EXECUTION_RUNBOOK.md   ← operational method
  ↓
docs/QA_FINDINGS_LOG.md              ← defect/fix/regression history
  ↓
docs/QA_CURRENT_HANDOFF.md           ← current main/checkpoint
```

The runbook explains **how** to continue. This handoff explains **where** to continue. The findings log explains **what went wrong and why**.
