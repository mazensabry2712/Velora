# Velora QA Findings Log

This log records defects discovered by the master QA program, the test that exposed each defect, the minimal fix, and the regression guard.

## Finding QA-BOOK-001 — Booking response returned HTTP 500

**Area:** Public booking

**Root cause:** The legacy web booking controller generated the customer queue tracking URL with a route parameter that the route does not define. The booking transaction itself could succeed, but response generation failed afterward.

**Fix:** Generate `/queue/status` with the public reference as a query parameter.

**Regression:** Golden booking scenario asserts the successful HTTP response and downstream records.

---

## Finding QA-SCHEMA-001 — business_rules schema drift

**Area:** Tenant booking rules

**Root causes discovered:**
- `BusinessRule` uses `key`, while older tenant schemas lacked it.
- Legacy `name` and `conditions` columns could remain `NOT NULL`, preventing inserts by the current model.

**Fix:** Tenant migration aligns the current `BusinessRule` contract and makes legacy blocking columns nullable without deleting legacy data.

**Regression:** The master booking scenario verifies the required columns and executes `BusinessRule::setValue()` followed by `getValue()`.

---

## Finding QA-SCHEMA-002 — appointment status history schema drift

**Area:** Appointment lifecycle

**Root cause:** The model writes to `appointment_status_history`, while the expected schema was not guaranteed in fresh tenant databases.

**Fix:** Added tenant migration for the canonical `appointment_status_history` table.

**Regression:** Master scenario verifies the table and all columns required by the model.

---

## Finding QA-QUEUE-001 — queue business date used created_at

**Area:** Queue / future appointments

**Root cause:** Queue readers and repository methods filtered daily data using `created_at` instead of the queue's explicit `queue_date`. A queue created today for a future appointment could therefore disappear from the future day's queue/status/dashboard views.

**Fix:** Queue readers and repository daily operations now use `queue_date`. Rows from older schemas where `queue_date` is null fall back to `created_at`.

**Affected operations:**
- Queue reader by date
- Customer queue lookup
- Public queue status
- Waiting-position calculation
- Estimated wait calculation
- Repository `getByDate()`
- Repository daily stats
- Repository `moveToNextDay()`

**Regression:** `QueueLifecycleScenarioTest::future_queue_is_read_by_business_date_not_creation_timestamp()` creates a future queue whose record was created today and verifies it is returned for the future business date.

---

## Finding QA-CUSTOMER-001 — Tenant Dashboard counted the wrong customer entity

**Area:** Tenant Dashboard / Customer module

**Root cause:** Public booking creates the canonical `Customer` entity, while the Tenant Dashboard was counting and listing `User` records with the `Customer` role. This could make a newly booked customer appear in the Customer module but not in Dashboard customer metrics/recent customers.

**Fix:** Dashboard customer count, new-customer count, and recent-customer projection now read from the canonical `Customer` model while preserving the existing view data contract.

**Regression:** `CustomerReconciliationScenarioTest::booking_customer_is_counted_by_customer_api_and_tenant_dashboard()` creates a customer through real booking, verifies the appointment-to-customer relationship, verifies the Customer API, and reconciles the Dashboard customer and appointment counts against database truth.

---

## Finding QA-NOTIF-001 — Notification delivery state must not depend on transient queue driver timing

**Area:** Notification Delivery / Booking Reconciliation

**Root cause:** The reconciliation test originally required a newly-created delivery to remain `queued`, but in a synchronous test queue the notification job can execute immediately and legitimately transition the delivery to `sent`.

**Fix:** Reconciliation now asserts durable invariants: the delivery exists, is linked to the correct appointment/public reference/event/channel, has `queued_at`, and is not failed. It no longer assumes a transient intermediate status.

**Regression:** `BookingReconciliationScenarioTest` verifies the actual delivery record produced by the public HTTP booking path.

---

## Finding QA-BILLING-001 — Moyasar webhook authentication was fail-open when the secret was missing

**Area:** Billing / Moyasar webhooks

**Root cause:** `MoyasarWebhookProcessor` only verified the HMAC signature when `services.moyasar.webhook_secret` was non-empty. A missing secret therefore allowed webhook processing to continue.

**Required fix:** Webhook authentication must fail closed: missing secret or invalid/missing signature must reject the webhook before any webhook ledger insert or subscription/payment side effect.

**Regression required:** Add coverage for missing secret, invalid signature, valid signature, duplicate event, verification failure, and safe retry semantics before marking Moyasar billing certified.

**Current status:** Identified and documented; not considered closed until the fix and MySQL CI regression tests pass.

---

## Test Infrastructure Policy

Every production defect discovered by Master QA must produce a regression test before the next feature family is accepted.

The test must validate the intended business outcome, not merely that an exception was thrown.

The canonical test environment is MySQL for feature/certification/concurrency scenarios. SQLite may be used for fast unit-level checks but is not sufficient evidence for tenant, locking, webhook, or billing certification.

## Package / Engineering Policy

Do not add a package merely to solve a problem that existing Laravel/PHP/project code can solve correctly. A mature package may be added only when it materially reduces complexity or risk for a real requirement. Every such decision must be documented.

## Current Certification Rule

A feature family is not considered complete until:

1. Its happy path passes.
2. Its negative and edge cases pass.
3. Its data invariants pass.
4. Its dependent projections reconcile.
5. Its regression tests pass in MySQL CI.
6. Its security/authorization and concurrency gates pass where applicable.
7. Any known production defect in that feature family is either fixed with regression coverage or explicitly blocks release.

## Current Handoff State

Completed/covered so far:

- Environment foundation
- Public booking golden flow
- Booking rules/negative cases
- Appointment lifecycle
- Queue lifecycle and business-date correctness
- Call-next locking/date scoping
- Customer/dashboard reconciliation
- Queue notification lifecycle and recovery basics

Next priority:

```text
Billing/Webhooks
→ Subscription lifecycle reconciliation
→ Tenant isolation matrix
→ Super Admin aggregation reconciliation
→ Reporting/export reconciliation
→ Deletion/cleanup safety
→ Browser smoke / final certification
```

Do not mark Velora production-certified merely because the existing global PHPUnit suite is green. Certification requires the master scenario suite, reconciliation, security, concurrency and billing gates above.