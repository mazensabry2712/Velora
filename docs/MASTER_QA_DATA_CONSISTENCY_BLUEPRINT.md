# Velora — Master QA Data Consistency Blueprint

## Purpose

This document is the canonical contract for the integrated QA environment of Velora.

The objective is not to prove isolated pages or endpoints. The objective is to prove that one business event produces one coherent business truth across:

- tenant database
- tenant API
- tenant UI/dashboard
- reports and exports
- notifications and delivery ledger
- subscription/billing state
- Super Admin views and aggregates
- activity/audit history where applicable

A release is not certified because a page returns `200` or an endpoint returns `201`. A critical scenario is certified only when the resulting business state reconciles across all applicable surfaces.

---

## 1. Canonical Truth Model

### Rule

**Database/business state is the source of truth. UI, API, dashboards, reports and Super Admin are projections of that truth.**

Tests must therefore calculate expected values from the canonical dataset and business rules, then compare each surface against those expectations.

Never create arbitrary dashboard expectations such as `assertSame(18, $dashboardCount)` unless `18` is derived from the scenario dataset or business event log.

Preferred pattern:

```text
Canonical dataset
      ↓
Business operation
      ↓
Database state
      ↓
Expected projection
      ├── Tenant Dashboard
      ├── Tenant API
      ├── Reports
      ├── Notifications
      └── Super Admin aggregation
```

---

## 2. Golden Dataset

The master suite must use deterministic, human-readable data with intentionally related records.

### Tenant A — Dental Clinic

Primary operational tenant used for full business journeys.

Suggested profile:

- 1 Tenant Admin
- 2 Staff
- 1 Assistant
- 1 additional restricted staff user where the role matrix requires it
- 3 services: Consultation, Cleaning, Whitening
- 1 inactive service for negative booking tests
- 2 resources/rooms
- working hours with at least one break
- at least one staff-specific time-off period
- at least one holiday
- at least 12 customers in distinct states
- appointments covering every important lifecycle state
- queue entries covering waiting, serving, completed, skipped and priority
- notification delivery records covering queued, sent and failed/retry cases
- at least one completed billable appointment
- subscription in a defined lifecycle state

### Tenant B — Medical Center

Second tenant dedicated primarily to isolation and aggregation tests.

Use:

- different services
- different staff
- different customers
- different appointment counts
- different subscription state
- different schedule/timezone where supported

No identifier that belongs to Tenant A should be reused as a logical business entity in Tenant B.

### Tenant C — Salon

Third tenant used to prove that platform-wide logic does not accidentally depend on Tenant A defaults.

Use different business configuration, schedule and service durations.

---

## 3. Dataset Design Rules

Every important record must be traceable through a business chain.

Example:

```text
Customer C-001
  ↓
Appointment A-001
  ↓
Service S-001
  ↓
Staff ST-001
  ↓
Resource R-001
  ↓
Queue Q-001
  ↓
Notification D-001
  ↓
Invoice I-001
  ↓
Payment T-001
```

Use the same chain when validating downstream projections.

### Forbidden test-data patterns

- unrelated random records created only to increase row counts
- duplicated records with no business purpose
- hard-coded dashboard numbers disconnected from persisted facts
- using one tenant's record IDs as another tenant's expected IDs
- relying only on model factories when the real application flow has important side effects

---

## 4. Scenario Types Required for Full Certification

### A. Environment / Boot

Verify:

- clean install
- Composer dependency resolution
- asset build
- fresh migrations
- tenant migrations
- scheduler registration
- queue configuration
- application health endpoints if present

### B. Platform Lifecycle

Verify:

```text
signup
→ tenant creation
→ provisioning
→ tenant database
→ verification
→ handoff
→ onboarding
→ operational tenant
```

Every failure point must prove recovery and absence of partial/inconsistent tenant state.

### C. Authentication

Cover:

- super admin login/logout
- tenant login/logout
- customer portal authentication where applicable
- password reset
- token lifecycle
- session invalidation
- invalid/expired credentials

### D. Authorization Matrix

Build an explicit matrix for:

- Super Admin
- Tenant Admin
- Staff
- Assistant
- Customer
- Guest/Public

For every protected operation test:

```text
allowed actor → success
forbidden actor → forbidden
unauthenticated actor → auth challenge
wrong tenant actor → forbidden/not found according to contract
```

### E. Tenant Isolation

This is a P0 category.

For Tenant A, attempt to access Tenant B data through:

- normal UI navigation
- direct route
- API endpoint
- manipulated numeric/string identifier
- foreign model ID
- altered tenant header/parameter
- reused token
- list/search/filter endpoints
- report/export endpoints

Repeat for read, update and delete.

### F. Configuration / Catalog

Test complete dependencies among:

```text
Service
↔ Category
↔ Staff
↔ Staff-Service
↔ Resource
↔ Schedule
↔ Booking availability
```

Changing a source configuration must be reflected everywhere it is used.

Examples:

- deactivate service → disappears from public booking
- remove staff-service relation → staff cannot be booked for that service
- deactivate resource → resource cannot be selected
- add time-off → availability changes
- holiday → availability becomes unavailable

### G. Customer Lifecycle

Cover:

- create
- update
- block/unblock
- booking history
- appointment history
- duplicate email handling
- customer data privacy
- portal visibility

### H. Public Booking Journey

The canonical happy path is:

```text
Public page
→ service discovery
→ staff selection
→ availability
→ customer details
→ booking creation
→ appointment
→ queue
→ confirmation
→ notification ledger
→ tracking
→ admin dashboard
→ reports
```

Every step must reconcile with the next step.

### I. Availability / Scheduling

Cover:

- working hours
- breaks
- holidays
- time-off
- service duration
- buffer before/after
- resources
- timezone conversion
- minimum advance booking
- maximum advance booking
- past times
- occupied times
- same-day policy

### J. Appointment State Machine

Exercise every supported valid state transition and every prohibited transition.

For each transition validate:

- appointment state
- queue state
- status history
- customer-visible state
- dashboard counts
- reports
- notification side effects
- invoice/payment consequences when applicable

### K. Queue State Machine

Cover:

```text
waiting
→ called
→ serving
→ completed
```

and:

```text
waiting → skipped
skipped → waiting   (when supported)
priority ordering
```

Test the invariant that an appointment cannot have more than one active queue position.

### L. Notifications / Delivery

For email, WhatsApp and every supported channel:

- queued
- sent
- failed
- retry
- duplicate suppression
- provider failure
- missing recipient
- delivery history

Notification failure must not roll back an otherwise successful core booking transaction unless the product explicitly defines transactional coupling.

### M. Subscription Lifecycle

Simulate time through:

```text
trial
→ active
→ grace
→ read_only
→ locked
→ deletion eligibility
```

Also cover:

- renewal
- upgrade
- downgrade
- cancellation
- trial extension where supported
- limits reached
- write protection
- access restoration

### N. Billing / Payments

Cover provider-independent commercial states plus provider-specific adapters.

Required cases:

- checkout success
- checkout failure
- pending transaction
- verified webhook
- invalid webhook
- duplicate webhook
- retry after processing failure
- out-of-order provider event
- subscription reconciliation
- invoice reconciliation
- payment transaction reconciliation
- refund/chargeback semantics where supported

No commercial state should be advanced solely because a client/browser reports success.

### O. Reporting / Export

For every important report:

1. derive expected rows/aggregates from the canonical dataset
2. load the report
3. compare totals
4. compare filtered totals
5. compare date boundaries
6. compare tenant boundaries
7. compare exported result where export exists

### P. Tenant Dashboard Reconciliation

For every dashboard metric define:

```text
metric
source table(s)
filter
status rules
date basis
timezone basis
aggregation rule
```

Then compare dashboard values to direct database-derived expectations.

Important examples include:

- total appointments
- today appointments
- confirmed today
- queue count
- customer count
- staff count
- new customers
- attendance rate
- cancellation rate
- revenue
- status distribution
- top services
- staff performance
- recent customers
- recent activities
- subscription usage/limits

### Q. Super Admin Reconciliation

Super Admin metrics must be validated from central data and, where a metric is tenant-derived, against the sum of the defined tenant-level facts.

Examples:

```text
active tenants
trial tenants
paid tenants
inactive tenants
new tenants
pending upgrade requests
```

For platform aggregates:

```text
Tenant A + Tenant B + Tenant C
        ↓
Expected platform aggregate
        ↓
Super Admin metric
```

Do not assume that `Tenant A + Tenant B + Tenant C` is the correct formula unless the product definition says the metric is additive.

### R. Cross-Surface Reconciliation

A single business event must be checked across all relevant projections.

#### Booking

```text
DB appointment
= customer relationship
= queue relationship
= availability result
= notification ledger
= dashboard impact
= report impact
```

#### Queue transition

```text
DB queue
= appointment state
= status history
= public queue state
= dashboard queue count
= notification side effect
```

#### Payment

```text
provider event
= webhook ledger
= payment transaction
= invoice/history
= subscription state
= tenant access
= dashboard subscription state
```

### S. Time Simulation

Use deterministic time travel for:

- trials
- expirations
- reminders
- weekly/monthly metrics
- billing periods
- deletion deadlines
- future appointments
- timezone boundaries

### T. Failure / Recovery

Inject failures at business boundaries:

- mail provider
- WhatsApp provider
- payment provider
- webhook verification
- job execution
- database transaction
- duplicate browser submission
- refresh/retry
- external service timeout

For every failure assert that the system is either fully rolled back or fully recoverable according to the intended consistency model.

### U. Concurrency

Use real MySQL integration tests for:

- two bookings for the same slot
- two `call next` queue actions
- concurrent queue transition
- concurrent webhook delivery
- duplicate request submission
- concurrent quota checks when quota is enforced

### V. Localization / RTL

Run the highest-value business journeys in every supported locale.

Verify:

- page text
- validation messages
- dates/times
- currency formatting
- route preservation
- stored locale preference
- RTL/LTR layout state

### W. Security / Abuse

Cover:

- rate limits
- malformed public requests
- enumeration resistance
- IDOR attempts
- CSRF/session boundaries where applicable
- webhook authenticity
- secret absence/fail-closed behavior
- unsafe file access
- tenant boundary bypasses

### X. Data Integrity / Invariants

Create a dedicated invariant layer that checks the whole dataset after major scenarios.

Examples:

- every appointment belongs to the current tenant
- every queue entry points to one valid appointment
- no appointment has two active queue entries
- cancelled/no-show appointments do not block availability
- inactive services are not publicly bookable
- staff-service relationships are valid
- invoice/payment relationships are valid according to lifecycle rules
- notification dedupe keys remain unique by business definition
- locked/read-only tenants cannot perform protected writes
- dashboard metrics equal their defined database truth

### Y. Browser Smoke / Critical UX

Use browser tests for a small set of high-value journeys:

- signup/verification
- tenant login
- onboarding
- public booking
- queue tracking
- admin dashboard
- appointment lifecycle
- subscription page
- billing checkout entry point
- language switching

Browser tests should assert behavior and visible business state, while database reconciliation remains in backend integration tests.

---

## 5. Golden Dataset Counts

The master dataset should deliberately contain enough records to exercise edge cases.

Recommended appointment distribution for the main tenant:

| State | Minimum examples |
|---|---:|
| pending | 3 |
| confirmed | 4 |
| waiting/queue-linked | 3 |
| serving | 1 |
| completed | 5 |
| cancelled | 2 |
| no_show | 2 |
| rescheduled | 2 |

These are scenario-design targets, not dashboard literals. The test suite must calculate actual expected metrics from persisted records after dataset construction.

---

## 6. Scenario Record Format

Every master scenario should document:

```text
ID
Actor
Tenant
Preconditions
Business action
Expected HTTP/API result
Expected database delta
Expected domain/event side effects
Expected notification delta
Expected tenant dashboard delta
Expected report delta
Expected Super Admin delta
Security expectations
Invariant checks
Recovery expectation
```

Example:

```text
BOOK-001
Actor: Public Customer
Tenant: Dental Clinic
Action: book Consultation with Dr. Ahmed at 09:00

Expected:
- appointment +1
- customer +1 or existing customer reused
- queue +1
- status history +1
- email delivery +1
- 09:00 disappears from available slots
- tenant dashboard total +1
- tenant dashboard today's count +1
- tenant report appointment count +1
- Super Admin only changes if the metric is tenant-derived and the event belongs in that aggregate
- all records remain inside Tenant A
```

---

## 7. Dashboard Metric Contract

Each metric should have one explicit definition.

Use a metric contract like:

```text
metric: confirmed_today
source: appointments
filter: date=today AND status=confirmed
clock: application/tenant timezone as defined by product
aggregation: count
```

Then test both:

1. the definition itself
2. each dashboard implementation against that definition

This prevents multiple pages from implementing slightly different meanings for the same metric.

---

## 8. Super Admin Metric Contract

For every Super Admin metric define one of:

- central-only metric
- tenant-count aggregate
- tenant-state aggregate
- additive tenant business metric
- non-additive platform metric

For example:

```text
paid_tenants
= distinct tenants with an active paid subscription
```

This should not silently change meaning between the HTML dashboard and JSON endpoint.

---

## 9. Test Pyramid for the Master Suite

Use four layers together.

### Layer 1 — Unit / Domain

Fast business-rule tests.

### Layer 2 — Feature / Integration

Database + tenant + API + services + side effects.

### Layer 3 — Reconciliation / Certification

Cross-surface assertions using the canonical dataset.

### Layer 4 — Browser / Smoke

Human-facing critical journeys.

Do not replace lower layers with browser tests. Do not replace browser smoke with API-only tests.

---

## 10. Certification Gates

The release cannot be marked certified when any of these fail:

- clean migration
- tenant migration
- tenant isolation
- role authorization
- critical booking flow
- availability correctness
- booking concurrency
- queue concurrency
- queue/appointment consistency
- notification consistency
- subscription transition enforcement
- webhook authenticity
- webhook idempotency/retry
- billing reconciliation
- dashboard reconciliation
- Super Admin reconciliation
- report reconciliation
- business invariants
- deletion/cleanup safety
- critical browser smoke

A red certification gate means **release blocked**, not “known failure”.

---

## 11. Anti-Flake Rules

Master QA must be deterministic.

- freeze time where dates matter
- use deterministic IDs/emails where uniqueness is part of the scenario
- clear relevant rate limits
- isolate tenant databases correctly
- avoid shared mutable static fixtures
- avoid test-order dependence
- run MySQL for database/concurrency behavior
- reset central and tenant state between tests/classes using a deliberate strategy
- never rely on a previous test to create a required record

---

## 12. What This Suite Can and Cannot Guarantee

The suite can provide a strong, repeatable release certification signal when all gates pass.

It cannot mathematically prove that an arbitrary future production input will never fail.

The engineering goal is therefore:

```text
No known critical path without a test
+
No critical cross-surface metric without reconciliation
+
No tenant boundary without an isolation test
+
No state machine without transition tests
+
No external provider without failure/retry tests
+
No release without a reproducible certification run
```

That is the standard Velora should use before calling a build production-ready.
