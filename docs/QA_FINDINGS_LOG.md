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

**Current status:** Fix and regression tests are committed to `main`; billing remains uncertified until the MySQL CI run passes and subscription/invoice/payment reconciliation is completed.

---

## Finding QA-TESTINFRA-001 — Tenant test transaction rollback depended on the current default connection

**Area:** Tenant QA infrastructure

**Root cause:** `TenantTestCase` started a tenant transaction, but `tearDown()` previously called `DB::rollBack()` through whichever connection was current at teardown time. Tests that explicitly ended tenancy before teardown could therefore roll back the wrong connection and leak the central fixture transaction into the next test.

**Fix implemented:** The test base now records the actual tenant connection name when tenancy is initialized, starts the tenant transaction on that connection, and explicitly rolls it back before ending tenancy. Central transaction rollback remains explicit through the configured central connection.

**Regression:** Tenant isolation tests run multiple methods in the same class without leaking the shared fixture tenant into a following test.

---

## Finding QA-ISOLATION-001 — Tenant token isolation needed an actual persisted Sanctum token model

**Area:** Tenant authorization tests

**Root cause:** The first QA isolation test used the `NewAccessToken` wrapper incorrectly. The middleware checks `currentAccessToken()` and its abilities; the test must attach the persisted `PersonalAccessToken` model, not the plain-text token wrapper.

**Fix implemented:** Isolation tests now attach the persisted `PersonalAccessToken` retrieved from the tenant database and verify tenant A access versus tenant B rejection.

**Regression:** `TenantIsolationSecurityScenarioTest` covers same-tenant acceptance and cross-tenant `403` rejection. `TenantIsolationResourceScenarioTest` additionally verifies tenant A Appointment/Queue records are not visible after switching to tenant B.

---

## Finding QA-SUPERADMIN-001 — Active tenant reconciliation must respect the Tenant model accessor contract

**Area:** Super Admin Dashboard / tenant aggregation

**Root cause:** The test initially counted only tenants with an explicit `data.active=true` JSON key, while `Tenant::active` treats a missing value as active by default. This made the test disagree with the actual Super Admin projection semantics.

**Fix:** Reconciliation now calculates active tenants through the same `Tenant::$active` accessor contract used by the application.

**Regression:** `SuperAdminReconciliationScenarioTest` verifies total, active, paid and trial tenant counts plus recent tenant identities against central DB truth, and separately verifies subscription statistics/revenue and per-plan active/trial counts.

---

## Finding QA-REPORT-001 — Reports counted a different customer population than the Tenant Dashboard

**Area:** Reports / Customer metrics

**Root cause:** `ReportService::getStats()` counted `User::role('Customer')`, while the canonical customer population is stored in the `customers` table and used by public booking and the Tenant Dashboard.

**Fix implemented:** `ReportService` now counts the canonical `Customer` model.

**Regression:** `ReportingReconciliationScenarioTest` creates a canonical Customer and verifies report customer totals equal `Customer::count()` and the Tenant Dashboard customer metric.

---

## Finding QA-DELETION-001 — Tenant purge hardcoded the central connection

**Area:** Permanent tenant deletion

**Root cause:** `PermanentlyDeleteExpiredTenants` queried and deleted billing/tenant records through a hardcoded `mysql` connection instead of the configured `tenancy.database.central_connection`.

**Fix implemented:** The command now resolves the configured central connection once and uses it for all central subscription/tenant-record operations.

**Regression:** `TenantDeletionSafetyScenarioTest` covers failed resource cleanup (tenant retained for retry) and successful cleanup (subscription and tenant central records removed after resource cleanup succeeds).

**Current status:** Code fix and tests are on `main`; CI certification pending.

---

## Finding QA-AUTH-001 — Tenant Staff/Assistant could mutate administrative configuration

**Area:** Tenant admin authorization / services / staff / settings

**Root cause:** The tenant admin route group admits `Admin Tenant|Staff|Assistant`, while the affected controllers had no method-level authorization and their FormRequests returned `authorize() = true`. As a result, Staff/Assistant could reach mutation methods for services, time slots, working-day configuration, staff-service assignment, staff CRUD, and tenant settings.

**Fix implemented:** Added a minimal role guard inside the affected mutation methods. `Admin Tenant` is required for service/schedule mutations, tenant settings writes, and staff create/update/delete. Read methods remain unchanged.

**Regression:** `AdminAuthorizationMatrixScenarioTest` verifies Staff can read but cannot mutate services, Assistant cannot mutate settings/services, and Admin Tenant can create a service.

**Current status:** Code fix and regression test are on `main`; fresh MySQL CI certification pending.

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

Covered with passing evidence in the completed Master QA run on `a2e97f1`:

- Environment foundation
- Public booking golden flow
- Booking rules/negative cases
- Appointment lifecycle
- Queue lifecycle and business-date correctness
- Call-next locking/date scoping
- Customer/dashboard reconciliation
- Queue notification lifecycle and recovery basics
- Moyasar webhook security and payment-verification scenarios

Added/fixed after that run and awaiting fresh MySQL CI evidence:

- Tenant token isolation
- Tenant resource isolation
- Tenant test transaction connection safety
- Super Admin tenant/subscription reconciliation
- Super Admin billing/revenue reconciliation
- Reporting customer reconciliation
- Tenant deletion safety
- Admin/Staff/Assistant authorization matrix

Recent Master QA result on `a2e97f1`: **42 passed, 4 failed**. Those four failures were diagnosed as test/projection/fixture/infrastructure issues and corrected on subsequent commits; the current `main` contains those corrections. Fresh CI on the current head is required before these additions are marked certified.

Next priority after the current CI gate:

```text
Billing/Webhooks reconciliation
→ Subscription access reconciliation
→ Full Tenant Isolation authorization matrix
→ Super Admin aggregation and revenue reconciliation
→ Reporting/export reconciliation
→ Deletion/cleanup safety
→ Browser smoke / final certification
```

Do not mark Velora production-certified merely because the existing global PHPUnit suite is green. Certification requires the master scenario suite, reconciliation, security, concurrency and billing gates above.
