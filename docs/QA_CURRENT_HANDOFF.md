# Velora QA Current Handoff

**Purpose:** Snapshot for the next chat/session. This file is intentionally small and is updated when the QA execution state changes materially.

## Repository

```text
Repository: mazensabry2712/Velora
Branch: main
```

## Current main head

```text
SHA: 76058aa1367212fcf991346fcb386e465412339f
```

This SHA includes the QA execution runbook link in `README.md` and the complete operational QA runbook in `docs/MASTER_QA_EXECUTION_RUNBOOK.md`.

## Method in one line

```text
Inspect current main → check current CI → find first confirmed discrepancy → regression test → root-cause diagnosis → minimal fix → focused regression → MySQL Master QA → document finding/status → continue.
```

## Non-negotiable rules

1. All accepted work for this QA workstream goes to `main`.
2. Never claim a test passed unless its result was actually observed.
3. Never claim certification while the relevant CI run is queued/in progress.
4. MySQL 8.4 is the certification database. SQLite is not sufficient evidence for tenant, locking, billing, webhook, concurrency or certification gates.
5. Every production defect requires a regression test.
6. Do not weaken assertions to obtain green CI; correct the test only when its contract is demonstrably wrong.
7. Do not add a package when native Laravel/PHP/project code is sufficient.
8. Reconcile dashboards/reports against canonical database truth.
9. Treat central and tenant databases as separate security/data boundaries.
10. Never overwrite documentation from memory and lose older findings.

## Completed coverage / hardening already implemented

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
```

## Latest CI evidence policy

The repository uses `.github/workflows/master-qa.yml` for the canonical Master QA suite and `.github/workflows/quality.yml` for broader quality checks. Both are driven from `main`; always match the run's `head_sha` to the current `main` SHA before treating its result as evidence.

At the time this snapshot was written, the latest observed CI for the preceding billing commit was still running/queued, and therefore the repository was **not certified**. The next chat must fetch fresh results for SHA `76058aa1367212fcf991346fcb386e465412339f` or a newer `main` head before making any certification claim.

## Last confirmed important CI failure batch

A previous Master QA run reported 42 passing tests and 4 failures. The failures were diagnosed as test/projection/test-infrastructure contract issues and corrected:

```text
BookingReconciliation projection assertion mismatch
Moyasar fixture missing subscription_plans.slug
Tenant isolation test used incorrect Sanctum token setup
Tenant test connection/rollback leakage
```

Those fixes were followed by additional hardening and must still receive fresh CI evidence on the current head.

## Current next gate

```text
Fresh CI on current main
→ Billing ↔ Subscription reconciliation
→ Full resource authorization matrix
→ Tenant isolation matrix for sensitive IDs
→ Super Admin financial/revenue reconciliation
→ Reporting / Excel export reconciliation
→ Deletion / storage/database cleanup certification
→ Playwright browser journeys
→ Full regression
→ Production go/no-go certification
```

## Key current findings

- `QA-BOOK-001`: legacy public booking response generated a route incorrectly.
- `QA-SCHEMA-001`: `business_rules` schema drift.
- `QA-SCHEMA-002`: appointment status history schema drift.
- `QA-QUEUE-001`: queue business date incorrectly used `created_at`.
- `QA-CUSTOMER-001`: dashboard customer metric used a different customer entity.
- `QA-NOTIF-001`: test depended on a transient notification queue state.
- `QA-BILLING-001`: Moyasar webhook authentication was fail-open when secret was missing.
- `QA-TESTINFRA-001`: tenant test rollback could target the wrong connection after tenancy changes.
- `QA-ISOLATION-001`: isolation test used the wrong Sanctum access-token object.
- `QA-SUPERADMIN-001`: active-tenant reconciliation initially disagreed with the Tenant accessor contract.
- `QA-REPORT-001`: reporting customer metric used a different source than the canonical Customer model.
- `QA-DELETION-001`: tenant purge hardcoded the central connection.
- `QA-AUTH-001`: Staff/Assistant could reach administrative configuration mutations.
- `QA-BILLING-002`: Moyasar subscription activation could use the default DB connection.
- `QA-AUTH-ONBOARDING-001`: onboarding mutations could be reached by Staff/Assistant even though onboarding changes tenant configuration and creates/activates core business records.

## Documentation map

```text
README.md
  ↓
docs/MASTER_QA_EXECUTION_RUNBOOK.md   ← how to work
  ↓
docs/QA_FINDINGS_LOG.md              ← defect history
  ↓
docs/QA_CURRENT_HANDOFF.md           ← current session snapshot
```

The runbook is the operational source of truth. This file is the short current-state checkpoint.
