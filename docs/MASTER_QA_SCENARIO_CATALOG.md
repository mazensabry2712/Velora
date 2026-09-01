# Velora — Master QA Scenario Catalog

This catalog defines the scenario families that must exist before a release can be considered fully certified.

## Scenario ID Convention

```text
ENV   Environment
LIFE  Lifecycle
AUTH  Authentication
PERM  Authorization
TEN   Tenant isolation
CFG   Configuration/catalog
CUS   Customer
BOOK  Public booking
AVAIL Availability/scheduling
APT   Appointment lifecycle
QUEUE Queue lifecycle
NOTIF Notifications
SUB   Subscription
PAY   Payments/billing
REP   Reports/exports
DASH  Tenant dashboard
SA    Super Admin
REC   Reconciliation
TIME  Time simulation
FAIL  Failure/recovery
CONC  Concurrency
I18N  Localization
SEC   Security/abuse
DATA  Data integrity
E2E   Browser smoke
OPS   Deployment/operations
```

---

## P0 — Release Blocking Scenarios

| ID | Scenario | Primary truth | Cross-checks |
|---|---|---|---|
| ENV-001 | Fresh central migration | DB schema | CI/MySQL |
| ENV-002 | Fresh tenant migration | Tenant DB schema | tenant boot |
| LIFE-001 | Signup → provisioning | central + tenant state | email/handoff |
| LIFE-002 | Verification single-use | tenant verification state | auth |
| AUTH-001 | Tenant login | session/token | dashboard |
| PERM-001 | Role matrix | authorization policy | UI/API |
| TEN-001 | Tenant A cannot read Tenant B | tenant DB boundary | API/UI |
| TEN-002 | Tenant A cannot modify Tenant B | tenant DB boundary | API/UI |
| TEN-003 | Tenant A cannot delete Tenant B | tenant DB boundary | API/UI |
| BOOK-001 | Real public booking journey | appointment/customer/queue | availability/dashboard/report |
| BOOK-002 | Duplicate booking | appointment count | availability |
| AVAIL-001 | Working hours | schedule truth | slots |
| AVAIL-002 | Break/time-off/holiday blocking | schedule truth | slots |
| APT-001 | Full appointment lifecycle | appointment state | queue/history/dashboard |
| QUEUE-001 | Full queue lifecycle | queue state | appointment/history/dashboard |
| QUEUE-002 | One appointment one active queue entry | invariant | DB |
| SUB-001 | Trial → expiry → read-only → locked | subscription state | access |
| PAY-001 | Verified payment state change | provider + DB | invoice/subscription |
| PAY-002 | Invalid webhook rejected | webhook auth | no commercial mutation |
| PAY-003 | Duplicate webhook idempotent | webhook ledger | one side-effect set |
| DASH-001 | Tenant dashboard metrics reconcile | DB-derived truth | all dashboard cards |
| SA-001 | Super Admin tenant counts reconcile | central truth | dashboard/API |
| REC-001 | Booking cross-surface reconciliation | DB truth | tenant UI/API/report/queue/notif |
| REC-002 | Billing cross-surface reconciliation | commercial truth | payment/invoice/subscription |
| DATA-001 | Global invariant sweep | all relevant tables | full dataset |

---

## P1 — High Value Scenarios

### Catalog / Staff / Schedule

```text
CFG-001 Create category
CFG-002 Create service
CFG-003 Activate/deactivate service
CFG-004 Assign staff to service
CFG-005 Remove staff-service relationship
CFG-006 Create resource
CFG-007 Restrict resource to service
CFG-008 Change service duration
CFG-009 Change buffers
CFG-010 Change working hours
CFG-011 Add break
CFG-012 Add staff time-off
CFG-013 Add holiday
```

### Customer

```text
CUS-001 Create customer from booking
CUS-002 Reuse existing customer by email
CUS-003 Update customer profile
CUS-004 Block customer
CUS-005 Blocked customer rejected from public booking
CUS-006 Customer appointment history
CUS-007 Customer privacy boundaries
```

### Booking / Availability

```text
BOOK-003 Inactive service cannot book
BOOK-004 Staff/service mismatch rejected
BOOK-005 Invalid resource rejected
BOOK-006 Past slot rejected
BOOK-007 Same-day policy
BOOK-008 Minimum advance rule
BOOK-009 Maximum advance rule
BOOK-010 Occupied slot rejected
BOOK-011 Timezone conversion
BOOK-012 Booking notification failure does not corrupt booking
```

### Appointment

```text
APT-002 Confirm
APT-003 Cancel
APT-004 Reschedule
APT-005 Complete
APT-006 No-show
APT-007 Invalid transition rejected
APT-008 Status history complete
APT-009 Completion/invoice consistency
```

### Queue

```text
QUEUE-003 Priority ordering
QUEUE-004 Call next
QUEUE-005 Serving
QUEUE-006 Complete
QUEUE-007 Skip
QUEUE-008 Return to waiting when supported
QUEUE-009 Public queue status
QUEUE-010 Queue dashboard reconciliation
```

### Notifications

```text
NOTIF-001 Email queued
NOTIF-002 Email sent
NOTIF-003 Email failure/retry
NOTIF-004 WhatsApp queued
NOTIF-005 WhatsApp failure/retry
NOTIF-006 Deduplication
NOTIF-007 Provider unavailable
NOTIF-008 Ledger matches business event count
```

### Subscription

```text
SUB-002 Renewal
SUB-003 Upgrade
SUB-004 Downgrade
SUB-005 Cancellation
SUB-006 Trial extension
SUB-007 User quota
SUB-008 Appointment quota
SUB-009 Read-only write restriction
SUB-010 Locked write restriction
SUB-011 Access restoration
```

### Billing / Webhooks

```text
PAY-004 Checkout failure
PAY-005 Pending transaction
PAY-006 Verified Stripe webhook
PAY-007 Verified Moyasar webhook
PAY-008 Missing webhook secret fails closed
PAY-009 Webhook processing retry
PAY-010 Out-of-order webhook
PAY-011 Invoice history consistency
PAY-012 Payment transaction consistency
PAY-013 Commercial state cannot advance from client-only success
```

### Reports / Dashboard

```text
REP-001 Appointment totals
REP-002 Status distribution
REP-003 Revenue totals
REP-004 Date filter boundaries
REP-005 Tenant filter boundaries
REP-006 Export matches on-screen filtered data
DASH-002 Today appointments
DASH-003 Confirmed today
DASH-004 Queue count
DASH-005 Customer count
DASH-006 Staff count
DASH-007 Attendance rate
DASH-008 Cancellation rate
DASH-009 Revenue
DASH-010 Top services
DASH-011 Staff performance
DASH-012 Recent customers/activities
DASH-013 Subscription limits
SA-002 Paid tenant count
SA-003 Trial tenant count
SA-004 Inactive tenant count
SA-005 New tenant count
SA-006 Pending upgrade requests
SA-007 HTML dashboard equals API dashboard data
```

---

## P1 — Reconciliation Scenarios

These should be implemented as a reusable certification layer rather than duplicated assertions in every test.

```text
REC-001 Booking projection reconciliation
REC-002 Appointment/queue reconciliation
REC-003 Customer/appointment reconciliation
REC-004 Availability/appointment reconciliation
REC-005 Notification/event reconciliation
REC-006 Invoice/payment reconciliation
REC-007 Subscription/access reconciliation
REC-008 Tenant dashboard/DB reconciliation
REC-009 Super Admin/central DB reconciliation
REC-010 Super Admin vs tenant aggregation reconciliation
REC-011 Report/DB reconciliation
REC-012 Export/on-screen reconciliation
```

---

## P1 — Security / Abuse Scenarios

```text
SEC-001 Unauthenticated protected route
SEC-002 Wrong role protected route
SEC-003 Cross-tenant IDOR read
SEC-004 Cross-tenant IDOR update
SEC-005 Cross-tenant IDOR delete
SEC-006 Cross-tenant list/filter bypass
SEC-007 Public endpoint enumeration
SEC-008 Public booking rate limit
SEC-009 Availability rate limit
SEC-010 Invalid webhook signature
SEC-011 Missing webhook secret
SEC-012 Replay webhook
SEC-013 Unsafe file access
SEC-014 Session invalidation
SEC-015 Malformed input / validation boundary
```

---

## P1 — Failure / Recovery Scenarios

```text
FAIL-001 Mail outage during booking
FAIL-002 WhatsApp outage during booking
FAIL-003 Payment provider timeout
FAIL-004 Invalid webhook payload
FAIL-005 Webhook processing exception
FAIL-006 Queue job failure and retry
FAIL-007 Database rollback during booking
FAIL-008 Duplicate browser submission
FAIL-009 Refresh during critical action
FAIL-010 Partial provisioning recovery
FAIL-011 Failed tenant deletion cleanup
```

Expected result must always be explicitly classified as one of:

```text
FULL ROLLBACK
or
COMMITTED CORE + RETRYABLE SIDE EFFECT
or
SAFE IDEMPOTENT DUPLICATE
```

No test should merely assert that an exception occurred.

---

## P1 — Concurrency Scenarios

```text
CONC-001 Two customers book same slot
CONC-002 Two requests submit same booking
CONC-003 Two staff click Call Next
CONC-004 Two queue mutations target same entry
CONC-005 Same webhook delivered concurrently
CONC-006 Simultaneous quota boundary writes
```

All database-sensitive cases must use MySQL in CI.

---

## P2 — Time / Localization / Operations

```text
TIME-001 Trial start
TIME-002 Trial warning
TIME-003 Trial expiry
TIME-004 Read-only boundary
TIME-005 Lock boundary
TIME-006 Deletion deadline
TIME-007 Appointment reminder window
TIME-008 Month boundary
TIME-009 Week boundary
TIME-010 Timezone date rollover
I18N-001 Arabic tenant journey
I18N-002 English tenant journey
I18N-003 Locale persistence
I18N-004 RTL/LTR state
I18N-005 Localized validation
OPS-001 Production-like env boot
OPS-002 Queue worker boot
OPS-003 Scheduler boot
OPS-004 Health check
OPS-005 Generated artifacts absent from repository
```

---

## 4. Mandatory Scenario Dependencies

A scenario must be executed against the minimum prerequisite graph, not against arbitrary fixtures.

```text
Tenant lifecycle
   ↓
Authentication
   ↓
Catalog + staff + schedules
   ↓
Customers
   ↓
Availability
   ↓
Booking
   ↓
Appointment
   ↓
Queue
   ↓
Notifications
   ↓
Invoice/payment
   ↓
Subscription/access
   ↓
Reports/dashboard
   ↓
Super Admin aggregation
```

This dependency graph is the reason the master suite must contain realistic, related data.

---

## 5. Coverage Completion Definition

The project is considered QA-complete only when:

1. Every P0 scenario exists and passes.
2. Every critical feature has happy, negative and edge coverage.
3. Every state machine has valid and invalid transitions covered.
4. Every tenant-sensitive feature has isolation coverage.
5. Every externally integrated provider has failure and retry coverage.
6. Every dashboard KPI has a metric contract and database reconciliation.
7. Every Super Admin aggregate has a central/tenant source definition and reconciliation.
8. Every critical report/export has database reconciliation.
9. Critical concurrency paths have MySQL tests.
10. Full regression and browser smoke pass from a clean environment.

---

## 6. Final Certification Rule

The desired signal is:

```text
BUSINESS EVENT
     ↓
DATABASE TRUTH
     ↓
DOMAIN INVARIANTS
     ↓
TENANT PROJECTION
     ↓
REPORT PROJECTION
     ↓
SUPER ADMIN PROJECTION
     ↓
NOTIFICATION / BILLING SIDE EFFECTS
     ↓
BROWSER USER EXPERIENCE
```

If any projection disagrees with the canonical truth, the scenario fails.
