# Velora Master QA Execution Runbook

## Purpose

This document is the operational source of truth for continuing Velora Master QA in another chat/session. It explains not only **what** is being tested, but **how** the QA work must be performed so that a future agent continues with the same discipline, evidence standard, and engineering style.

This runbook applies to the `main` branch of `mazensabry2712/Velora`.

---

## 1. Primary Goal

The goal is **system certification**, not a large test count.

A feature family is considered complete only when its behavior is proven across the complete chain:

```text
UI / HTTP request
      ↓
Application / Domain behavior
      ↓
Database state
      ↓
Events / Jobs / Notifications
      ↓
Dependent projections
      ↓
Tenant Dashboard / Reports / Super Admin
      ↓
Business invariants
      ↓
Security / authorization / concurrency
```

A green endpoint test alone is not sufficient.

The core question for every scenario is:

> Does the same business fact remain consistent everywhere that depends on it?

Examples:

```text
A booking is created
→ appointment exists
→ customer relationship is correct
→ queue entry is correct
→ availability changes correctly
→ notification delivery exists
→ tenant dashboard counts it
→ reports count it
→ super-admin aggregation counts it where applicable
```

---

## 2. Source of Truth Hierarchy

When two parts of the application disagree, do not decide based on whichever value is easiest to assert.

Use this order:

1. Current production schema/migration contract.
2. Current domain/application behavior.
3. Canonical model/repository used by the production path.
4. Database state created by the real workflow.
5. Projection/reader/controller output.
6. Test fixture assumptions.
7. Documentation written before the latest code changes.

If old documentation disagrees with current code and tests, the code/schema must be investigated first and the documentation must be corrected.

Never preserve a stale number or behavior merely because it appears in an older document.

---

## 3. Required Engineering Policy: Test First, Then Minimal Fix

For every suspected defect:

```text
Observe discrepancy
      ↓
Prove it with a focused regression test
      ↓
Identify the smallest root cause
      ↓
Apply the smallest correct production fix
      ↓
Run focused regression
      ↓
Run Master QA in MySQL CI
      ↓
Document finding + fix + regression
      ↓
Only then continue
```

Do **not** change an assertion merely to make a test green unless the assertion is proven to be testing the wrong contract.

If a test is wrong because it assumes a transient state, replace it with a durable business invariant and document why.

Do not hide failures, skip tests, weaken assertions, or add broad mocks just to obtain green CI.

---

## 4. Package / No-Over-Engineering Policy

Do not add a package when Laravel/PHP/project code can solve the requirement clearly and safely.

A package is justified only when it materially reduces complexity or risk for a real requirement.

Before adding a package:

```text
Existing Laravel capability?
Existing project abstraction?
Simple native implementation sufficient?
Operational risk reduced?
Long-term maintenance justified?
```

If native code is sufficient, use native code.

A production fix should normally be local, explicit, testable, and easy for another engineer to understand.

---

## 5. Canonical Test Environment

The certification environment is **MySQL 8.4 + PHP 8.4**.

The Master QA workflow intentionally runs:

```text
DB_CONNECTION=mysql
TENANCY_CENTRAL_CONNECTION=mysql
php artisan migrate --force
php artisan test tests/Feature/QA --compact
```

The CI workflow provisions MySQL 8.4 and PHP 8.4 and runs the complete `tests/Feature/QA` suite. SQLite may be used for fast unit checks, but SQLite is not sufficient certification evidence for:

- tenant isolation
- row locking / concurrency
- webhook/billing behavior
- central vs tenant connection boundaries
- final production gates

See `.github/workflows/master-qa.yml` for the authoritative CI configuration.

---

## 6. How a New Chat Must Start

Before modifying anything:

### Step 1 — Read project state

Read these first:

```text
README.md
docs/PROJECT_STATUS.md
docs/ARCHITECTURE.md
docs/TESTING_QA_PLAN.md
docs/SECURITY_TENANCY_AUDIT.md
docs/BILLING_HARDENING.md
docs/PRODUCTION_CHECKLIST.md
docs/QA_FINDINGS_LOG.md
docs/MASTER_QA_EXECUTION_RUNBOOK.md
```

### Step 2 — Inspect current `main`

Never assume a change exists because it was mentioned in an older conversation.

Confirm the actual current `main` head and inspect the relevant files from `main`.

### Step 3 — Check CI evidence

Use the latest workflow run for the current `main` SHA.

Distinguish clearly between:

```text
queued
in_progress
success
failure
cancelled
```

Never call something certified while its latest relevant CI is queued or running.

### Step 4 — Continue from the first unclosed gate

Use this order unless a concrete production blocker requires otherwise:

```text
CI baseline
→ Booking
→ Appointment
→ Queue
→ Customer
→ Notifications
→ Tenant Isolation
→ Authorization
→ Billing / Webhooks
→ Subscription lifecycle
→ Super Admin reconciliation
→ Reports / Exports
→ Deletion / Cleanup
→ Browser / E2E
→ Full regression
→ Production certification
```

If a previous stage has a failing CI result, fix that before adding unrelated feature tests.

---

## 7. Golden Dataset Philosophy

Use deterministic, realistic, relational data.

Do not create unrelated random rows unless the scenario specifically needs randomness.

A useful tenant scenario is conceptually:

```text
Tenant A — Dental Clinic
├── Admin Tenant
├── Staff
├── Assistant
├── Customers
├── Services
├── Staff/Service relationships
├── Working Hours
├── Breaks
├── Time Off
├── Holidays
├── Resources
├── Appointments
├── Queue Entries
├── Notifications
├── Invoices
└── Subscription

Tenant B — Medical Center
└── Separate equivalent dataset
```

Relationships must be intentional so a single business event can be traced end-to-end.

Example:

```text
Customer #101
    ↓
Appointment #5001
    ↓
Service
    ↓
Staff
    ↓
Resource
    ↓
Queue Entry
    ↓
Notification Delivery
    ↓
Dashboard / Report
```

This is more valuable than generating 500 unrelated records.

---

## 8. Reconciliation Rule

Every important projection must be reconciled against canonical DB truth.

Examples:

```text
Customer::count()
    == Tenant Dashboard customer metric
    == Report customer metric
```

```text
TenantSubscription counts/sums
    == Super Admin subscription statistics
```

```text
Today's appointments in DB
    == Appointment dashboard
    == relevant report totals
```

For any mismatch:

```text
Do not immediately change the test.
Trace the data source.
Find which layer uses a different business definition.
Then decide whether the canonical source or the projection is wrong.
```

---

## 9. Business Invariants

Invariants are rules that must remain true regardless of UI path.

Core examples:

### Tenant

```text
Tenant A cannot read Tenant B records.
Tenant A cannot update Tenant B records.
Tenant A cannot delete Tenant B records.
```

### Appointment

```text
Completed appointment has valid customer/staff/service relationship.
Cancelled appointment is not treated as available service time incorrectly.
An occupied slot cannot be booked twice.
```

### Queue

```text
One appointment cannot have two active queue entries.
Call Next cannot serve a future queue for today's operation.
Priority ordering is respected.
Only one concurrent caller may acquire the same waiting row.
```

### Customer

```text
Booking-created customers appear in Customer module and dependent projections.
```

### Notifications

```text
Same business event/channel/recipient does not generate duplicate delivery records.
Provider failure does not silently become success.
Retry remains possible when the delivery is retryable.
```

### Billing

```text
Unauthenticated webhook cannot mutate billing state.
Duplicate webhook cannot apply a second business mutation.
Provider verification must succeed before paid state is trusted.
```

### Subscription

```text
Subscription lifecycle dates are deterministic.
Read-only/locked state is enforced on write operations.
Permanent deletion only occurs after the actual configured deletion deadline.
```

---

## 10. Queue Testing Method

Queue requires more than CRUD testing.

Always test:

```text
business date
↓
waiting
↓
priority
↓
call next
↓
serving
↓
complete / skip / return waiting
↓
notification event
↓
delivery ledger
↓
public status/dashboard
```

For date-related behavior, `queue_date` is the business date.
`created_at` is the record creation timestamp.
They must not be treated as interchangeable.

For `Call Next`, test both:

```text
future queue must not be selected for today's operation
```

and:

```text
two simultaneous calls
→ one waiting entry becomes serving
→ same row cannot be claimed twice
```

The production implementation uses a transaction + row locking for this critical operation.

---

## 11. Notification Testing Method

Do not assert transient states blindly.

A synchronous queue driver may execute a queued job immediately. Therefore this:

```text
status == queued
```

may be a false test assumption.

Prefer durable invariants:

```text
delivery exists
correct event/reference/channel
queued_at exists
sent_at exists when execution succeeds
failed_at exists when terminal failure occurs
last_error exists when failure occurs
attempts is incremented appropriately
```

For event-driven flows:

```text
production event
→ listener
→ delivery ledger
→ job
→ provider
```

Test both event/listener contracts and full HTTP integration where appropriate.

---

## 12. Billing / Webhook Testing Method

Webhook testing is security-sensitive.

For every provider:

```text
missing secret
invalid signature
missing signature
invalid payload
valid signature
unknown event
valid paid event
duplicate event
provider verification failure
processing exception
retry
out-of-order event
```

Required rule:

```text
Authentication must happen before billing side effects.
```

For Moyasar this means:

```text
secret missing → reject
signature invalid → reject
```

before:

```text
webhook ledger insertion
payment verification
subscription activation
```

For Stripe, SDK signature verification remains the boundary.

For every successful payment scenario reconcile:

```text
provider event
→ webhook_events
→ payment verification
→ TenantSubscription
→ invoice/transaction state
→ tenant access state
→ Super Admin statistics
```

---

## 13. Subscription Lifecycle Testing Method

The current domain constants are:

```text
TRIAL_DAYS      = 7
READ_ONLY_DAYS  = 14
LOCKED_DAYS     = 6
```

Therefore the modeled deletion deadline is:

```text
7 + 14 + 6 = 27 days from the trial anchor
```

Do not copy older documentation values without checking the current domain implementation.

Test transitions with deterministic time:

```text
trial
→ active / conversion where applicable
→ read_only
→ locked
→ deletion eligible
```

Then verify access rules independently from state calculations.

---

## 14. Tenant Isolation Testing Method

Isolation must be tested at multiple layers.

### Token layer

```text
Tenant A token + Tenant A
→ allowed

Tenant A token + Tenant B
→ 403
```

### Resource layer

Create equivalent resources in both tenants and test:

```text
A appointment ID under B context
A customer ID under B context
A queue ID under B context
A billing/resource ID under wrong context
```

Expected result is denial/not found according to the route's intended security contract.

### Database layer

Switch tenancy context and verify canonical queries cannot expose records belonging to another tenant.

Never declare tenant isolation secure from middleware tests alone.

---

## 15. Authorization Matrix Method

For each sensitive resource, explicitly define:

```text
Anonymous
Customer
Assistant
Staff
Admin Tenant
Super Admin
```

Then test:

```text
read
create
update
delete
state transition
export
sensitive action
```

Do not blindly make every resource Admin-only.

First inspect current business behavior and existing policy.
If a permission is ambiguous, create a documented policy decision before changing it.

The recent hardening correctly restricted administrative configuration such as:

```text
Service mutations
Schedule mutations
Staff create/update/delete
Tenant settings writes
Onboarding mutations
```

to `Admin Tenant`, while leaving read operations and potentially intentional Staff/Assistant operational flows untouched until separately proven.

---

## 16. Test Infrastructure Rules

Tests must not contaminate each other.

Tenant tests are especially sensitive because there are two database scopes:

```text
central database
tenant database
```

The test base must remember the actual tenant connection it began with and roll it back explicitly before ending tenancy.

Never rely on the current/default connection after tenancy context has changed.

For Central models, use the configured:

```text
tenancy.database.central_connection
```

not a hardcoded connection name when the code's contract is central-connection based.

This rule applies to both production code and tests.

---

## 17. Reporting and Dashboard Method

Treat every dashboard/report metric as a projection.

For each metric document:

```text
metric name
canonical source table/model
filters
period definition
status definition
aggregation definition
```

Then reconcile the projection with raw DB truth.

Example:

```text
Customer count
→ Customer model
→ not a legacy User role count
```

If two surfaces count different populations, create a finding rather than silently accepting the discrepancy.

---

## 18. Super Admin Method

Super Admin reads the central platform truth.

Reconcile at minimum:

```text
total tenants
active tenants
paid tenants
trial tenants
total subscriptions
active subscriptions
trial subscriptions
revenue
monthly revenue
plan-level counts
recent tenants
```

Do not use tenant-local DB data as the direct source for central platform statistics unless the architecture explicitly defines that behavior.

---

## 19. Deletion / Cleanup Method

Deletion is a distributed lifecycle, not one SQL `DELETE`.

Test:

```text
eligibility
→ resource cleanup
→ tenant database cleanup
→ storage cleanup
→ central subscription cleanup
→ tenant central record cleanup
```

Failure scenario:

```text
resource cleanup fails
→ tenant remains recoverable
→ subscription remains
→ retry remains possible
```

Success scenario:

```text
resource cleanup succeeds
→ dependent central records are removed
→ tenant is removed according to the product's deletion policy
```

Do not claim deletion safety until partial failure has been tested.

---

## 20. CI Interpretation Rules

Always separate these facts:

```text
Test file exists
≠
Test was executed
```

```text
Test passed in one local environment
≠
MySQL CI passed
```

```text
One workflow passed
≠
Full production certification
```

The authoritative Master QA workflow is `.github/workflows/master-qa.yml` and runs all `tests/Feature/QA` scenarios on MySQL. The Quality workflow separately checks Composer, security audit, migrations, full PHPUnit, Pint, npm audit and frontend build.

When CI reports failures:

1. Capture exact failing test names.
2. Identify whether it is production code, schema, fixture, test infrastructure, or test expectation.
3. Fix root cause only.
4. Add/adjust regression guard.
5. Re-run on a new `main` SHA.
6. Do not merge a false-green workaround.

---

## 21. Documentation Method

Every meaningful change must leave an audit trail.

For a production defect:

```text
QA_FINDINGS_LOG.md
```

must record:

```text
Finding ID
Area
Root cause
Fix
Regression test
Current status
```

For methodology changes, update this runbook.

For project status changes, update the appropriate project-status/QA documentation.

Do not replace the findings log with an abbreviated summary that drops older findings.

When updating a file through GitHub Contents API:

```text
fetch current file
→ use current blob SHA
→ replace complete file deliberately
```

Never blindly overwrite a file using an old SHA or an old conversation copy.

---

## 22. Git / Main Branch Discipline

All accepted production/test changes for this workstream must be on `main`.

Before telling the user that a change is in the repository:

```text
verify main head
verify file path on main
verify commit SHA
```

Branches/PRs may be used for review when appropriate, but do not leave the user with the impression that a fix exists on `main` if it only exists on a side branch.

Never claim a change was locally executed when the current environment did not execute it.

When a GitHub Actions run is queued or in progress, report that state honestly.

---

## 23. Definition of Done for a Feature Family

A feature family is complete only when:

```text
[ ] Happy path
[ ] Negative paths
[ ] Edge cases
[ ] Database invariants
[ ] Cross-surface reconciliation
[ ] Authorization
[ ] Tenant isolation where relevant
[ ] Concurrency where relevant
[ ] Failure/recovery where relevant
[ ] Notification side effects where relevant
[ ] CI on MySQL
[ ] Regression protection
[ ] Documentation
```

---

## 24. Current Velora Coverage State

### Verified earlier by Master QA runs

```text
Environment foundation
Public booking golden flow
Booking rules / negative cases
Appointment lifecycle
Queue lifecycle
Queue business-date behavior
Call-next locking/date scoping
Customer/dashboard reconciliation
Queue notification lifecycle
Notification recovery basics
Moyasar webhook security/payment verification
```

### Added / hardened and requiring fresh CI evidence on the current head when applicable

```text
Tenant token isolation
Tenant resource isolation
Tenant test connection safety
Super Admin tenant/subscription reconciliation
Reporting customer reconciliation
Tenant deletion safety
Expanded authorization matrix
Onboarding authorization
Moyasar central connection activation
Stripe central connection hardening
```

Do not treat the list above as a certification result. The current SHA's CI is the authority.

---

## 25. Current Known Architectural Boundaries

Important boundaries observed during QA:

```text
Central DB
    ├── tenants
    ├── domains
    ├── subscription_plans
    └── tenant_subscriptions

Tenant DB
    ├── users
    ├── customers
    ├── services
    ├── staff
    ├── appointments
    ├── queues
    ├── notifications
    ├── reports/data
    └── tenant settings
```

The exact schema must always be confirmed against current migrations/models before writing a new scenario.

The project contains legacy compatibility layers. Do not remove them casually. Consolidation is a later cleanup task unless a concrete defect requires it.

---

## 26. What Not to Do

Never:

```text
❌ Add tests only for line coverage.
❌ Assert implementation details instead of business behavior.
❌ Change a production rule just to satisfy an old test.
❌ Weaken authorization because a UI needs to pass.
❌ Trust SQLite for locking/billing/tenancy certification.
❌ Add a package for a problem Laravel already solves.
❌ Claim CI passed while it is queued/in progress.
❌ Claim local execution when the environment could not execute it.
❌ Rewrite documentation from memory and accidentally drop findings.
❌ Treat a single dashboard as the source of truth for itself.
❌ Declare tenant isolation from middleware alone.
❌ Declare billing safe without webhook authentication/idempotency/reconciliation.
```

---

## 27. Recommended Continuation Order

When the current CI is green enough to proceed:

```text
1. Finish Billing ↔ Subscription reconciliation
2. Complete resource-level authorization matrix
3. Complete tenant isolation matrix for sensitive resource IDs
4. Complete Super Admin financial/tenant reconciliation
5. Complete Reports and Excel export reconciliation
6. Complete permanent deletion + cleanup + retry verification
7. Browser smoke / Playwright business journeys
8. Full regression suite
9. Production checklist
10. Final certification report
```

If a new P0/P1 defect appears, stop the sequence and use the test-first/minimal-fix/documentation process.

---

## 28. Handoff Template for the Next Chat

The next chat should be able to begin with this compact state check:

```text
Repository: mazensabry2712/Velora
Branch: main

Read:
- README.md
- docs/MASTER_QA_EXECUTION_RUNBOOK.md
- docs/QA_FINDINGS_LOG.md
- docs/TESTING_QA_PLAN.md
- docs/SECURITY_TENANCY_AUDIT.md
- docs/BILLING_HARDENING.md

Then:
1. Inspect current main SHA.
2. Inspect latest Master QA CI for that SHA.
3. Inspect failures before adding tests.
4. Apply test-first → minimal fix → regression → CI → documentation.
5. Keep all accepted changes on main.
6. Do not declare certification without fresh MySQL evidence.
```

---

## 29. Certification Standard

Final Velora certification means:

```text
Business flows are correct.
Data is internally consistent.
Tenant isolation is enforced.
Authorization is intentional.
Concurrency is safe for critical operations.
Notifications are reliable and observable.
Billing webhooks are authenticated and idempotent.
Subscription state controls access correctly.
Dashboards/reports reconcile with canonical data.
Deletion is safe and retryable.
MySQL CI is green on the final main SHA.
Browser smoke tests are green.
Known P0/P1 defects are closed or explicitly block release.
Documentation matches the actual repository state.
```

The final output should be a release decision, not merely a test count.
