# Velora QA Findings Log

This log records defects discovered by the master QA program, the minimal fix, and the regression guard.

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
- Legacy `name`, `conditions`, and `actions` columns could remain `NOT NULL`, preventing inserts by the current model.

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

**Regression:** `CustomerReconciliationScenarioTest` creates a customer through real booking, verifies the appointment-to-customer relationship, verifies the Customer API, and reconciles Dashboard counts against database truth.

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

**Fix implemented:** Webhook authentication now fails closed. A missing secret or a missing/invalid signature is rejected before JSON processing, webhook-ledger insertion, payment verification, subscription mutation, or other side effects.

**Regression added:**
- missing secret
- invalid signature
- valid signature
- duplicate event
- successful `payment.paid` processing with payment verification
- processing failure removes the unprocessed event so provider retry is possible

**Current status:** Fix and regression tests are on the QA history; fresh MySQL CI evidence is required before billing certification.

---

## Finding QA-TESTINFRA-001 — Tenant test transaction rollback depended on the current default connection

**Area:** Tenant QA infrastructure

**Root cause:** `TenantTestCase` started a tenant transaction, but `tearDown()` previously called `DB::rollBack()` through whichever connection was current at teardown time. Tests that explicitly ended tenancy before teardown could therefore roll back the wrong connection and leak the central fixture transaction into the next test.

**Fix implemented:** The test base records the actual tenant connection and rolls it back explicitly before ending tenancy. Central transaction rollback remains explicit through the configured central connection.

**Regression:** Tenant isolation tests execute multiple methods without leaking the shared fixture tenant into a following test.

---

## Finding QA-ISOLATION-001 — Tenant token isolation needed an actual persisted Sanctum token model

**Area:** Tenant authorization tests

**Root cause:** The first QA isolation test used the `NewAccessToken` wrapper incorrectly. The middleware checks `currentAccessToken()` and its abilities; the test must attach the persisted `PersonalAccessToken` model.

**Fix implemented:** Isolation tests use the persisted Sanctum token model and verify same-tenant acceptance versus cross-tenant rejection.

**Regression:** `TenantIsolationSecurityScenarioTest` covers token scoping and `TenantIsolationResourceScenarioTest` covers tenant A Appointment/Queue visibility after switching to tenant B.

---

## Finding QA-SUPERADMIN-001 — Active tenant reconciliation must respect the Tenant model accessor contract

**Area:** Super Admin Dashboard / tenant aggregation

**Root cause:** The test initially counted only tenants with an explicit `data.active=true` JSON key, while `Tenant::active` treats a missing value as active by default.

**Fix:** Reconciliation uses the same active-state contract as the application.

**Regression:** `SuperAdminReconciliationScenarioTest` verifies tenant, subscription and recent-tenant metrics against central DB truth.

---

## Finding QA-REPORT-001 — Reports counted a different customer population than the Tenant Dashboard

**Area:** Reports / Customer metrics

**Root cause:** `ReportService::getStats()` counted `User::role('Customer')`, while the canonical customer population is stored in `customers` and used by public booking/Dashboard.

**Fix:** `ReportService` uses the canonical `Customer` model for customer metrics.

**Regression:** `ReportingReconciliationScenarioTest` compares report customer totals with `Customer::count()` and the Tenant Dashboard customer metric.

---

## Finding QA-DELETION-001 — Tenant purge hardcoded the central connection

**Area:** Permanent tenant deletion

**Root cause:** `PermanentlyDeleteExpiredTenants` used a hardcoded `mysql` connection rather than the configured central connection.

**Fix implemented:** The command resolves and uses the configured central connection for central subscription/tenant operations.

**Regression:** `TenantDeletionSafetyScenarioTest` covers failed resource cleanup (tenant retained for retry) and successful cleanup.

**Current status:** Fix/test are part of the QA history; fresh MySQL CI evidence is required for certification.

---

## Finding QA-AUTH-001 — Tenant Staff/Assistant could mutate administrative configuration

**Area:** Tenant admin authorization / services / staff / settings

**Root cause:** The tenant admin route group admitted `Admin Tenant|Staff|Assistant`, while affected controllers had no method-level authorization and FormRequests returned `authorize() = true`. This allowed Staff/Assistant to reach service, schedule, staff and settings mutations.

**Fix implemented:** Added minimal role guards. `Admin Tenant` is required for service/schedule mutations, tenant settings writes, and staff create/update/delete. Read methods remain unchanged.

**Regression:** `AdminAuthorizationMatrixScenarioTest` verifies Staff read vs mutation behavior, Assistant write rejection, and Admin Tenant service mutation.

---

## Current Expanded Authorization Coverage

`AuthorizationMatrixExpandedScenarioTest` adds regression coverage for:

- Staff cannot create/delete staff accounts.
- Assistant cannot create time slots.
- Assistant cannot change working-day configuration.
- Tenant Admin can create a time slot and create a staff account.

This extends the authorization gate without changing Queue/Appointment permissions whose Staff/Assistant behavior may be intentional and requires separate business-policy evidence.

---

## Test Infrastructure Policy

Every production defect discovered by Master QA must produce a regression test before the next feature family is accepted.

The canonical certification environment is MySQL. SQLite may be used for fast unit checks but is not sufficient evidence for tenant, locking, webhook, billing, or certification gates.

## Package / Engineering Policy

Do not add a package merely to solve a problem that existing Laravel/PHP/project code can solve correctly. A package is justified only when it materially reduces complexity or risk for a real requirement. Such decisions must be documented.

## Current Certification Rule

A feature family is not complete until:

1. Happy path passes.
2. Negative and edge cases pass.
3. Data invariants pass.
4. Dependent projections reconcile.
5. Regression tests pass in MySQL CI.
6. Security/authorization and concurrency gates pass where applicable.
7. Known production defects are fixed with regression coverage or explicitly block release.

## Current Handoff State

The verified `main` history currently includes the Master QA foundation and the authorization hardening up to commit `270b4e3`. Additional later experiments exist as separate Git objects and are **not considered part of `main`** until explicitly merged or recreated on `main`.

Passing evidence from the completed Master QA run on `a2e97f1` includes:

- Environment foundation
- Public booking golden flow
- Booking rules/negative cases
- Appointment lifecycle
- Queue lifecycle and business-date correctness
- Call-next locking/date scoping
- Customer/dashboard reconciliation
- Queue notification lifecycle and recovery basics
- Moyasar webhook security and payment-verification scenarios

The latest required work on top of the current main line is:

```text
Fresh MySQL CI for current main
→ Billing/Webhooks reconciliation
→ Subscription access reconciliation
→ Full tenant/resource authorization matrix
→ Super Admin aggregation/revenue reconciliation
→ Reporting/export reconciliation
→ Deletion/cleanup safety
→ Browser smoke / final certification
```

Do not mark Velora production-certified merely because the existing global PHPUnit suite is green. Certification requires the master scenario suite, reconciliation, security, concurrency and billing gates above.
