# Velora QA Current Handoff

**Purpose:** Snapshot for the next chat/session. This file is intentionally small and is updated whenever QA execution state changes materially. It must never claim a gate is closed unless the relevant evidence is present.

## Repository

```text
Repository: mazensabry2712/Velora
Branch: main
```

## Current main head

```text
SHA: e5eed0bec3b4e4b9721f9fcb156c14627fb7612d
```

This SHA is the current `main` head after the QA remediation/cleanup commits. Always verify `refs/heads/main` before continuing.

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
Tenant test transaction connection safety (remediation added; fresh CI pending)
Tenant token/resource isolation tests (fresh CI pending)
Super Admin tenant/subscription reconciliation tests
Reporting customer-source reconciliation
Tenant deletion safety tests (remediation added; fresh CI pending)
Service/Staff/Settings authorization hardening
Expanded authorization tests
Onboarding mutation authorization hardening (remediation added; fresh CI pending)
Stripe central-connection hardening
QA suite cleanup: temporary marker/placeholder files removed

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

## Remediation added after Run #120

```text
TenantTestCase
→ concrete tenant/central Connection rollback

OnboardingController
→ Admin Tenant-only mutation guard

MoyasarCentralConnectionScenarioTest
→ self-contained valid SubscriptionPlan fixture

PermanentlyDeleteExpiredTenants
→ explicit central connection for Tenant lookup

QA suite
→ temporary placeholder/marker files removed
```

Full remediation details are preserved in `docs/QA_RUN_120_POSTMORTEM.md` and `docs/QA_FINDINGS_LOG.md`.

## Current CI requirement

The current certification target is the exact `main` SHA above:

```text
e5eed0bec3b4e4b9721f9fcb156c14627fb7612d
```

The latest Master QA run for that head is still executing. Until it completes successfully, the repository remains **not certified**.

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
Finish Master QA on current main
→ close any remaining failures
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
docs/QA_RUN_120_POSTMORTEM.md        ← detailed CI failure/remediation report
```
