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

**Regression:** `QueueLifecycleScenarioTest::future_queue_is_read_by_business_date_not_creation_timestamp()` creates a future queue whose record was created today and verifies it is returned for the future business date.

---

## Finding QA-CUSTOMER-001 — Tenant Dashboard counted the wrong customer entity

**Area:** Tenant Dashboard / Customer module

**Root cause:** Public booking creates the canonical `Customer` entity, while the Tenant Dashboard was counting/listing `User` records with the `Customer` role. This could make a newly booked customer appear in the Customer module but not in Dashboard metrics.

**Fix:** Dashboard customer count, new-customer count, and recent-customer projection now read from the canonical `Customer` model while preserving the existing view contract.

**Regression:** `CustomerReconciliationScenarioTest` creates a customer through real booking, verifies the appointment relationship, Customer API, and dashboard counts against database truth.

---

## Finding QA-NOTIF-001 — Notification delivery state must not depend on transient queue driver timing

**Area:** Notification Delivery / Booking Reconciliation

**Root cause:** The reconciliation test originally required a newly-created delivery to remain `queued`, but a synchronous test queue can execute immediately and transition it to `sent`.

**Fix:** Reconciliation asserts durable invariants: delivery exists, correct appointment/reference/event/channel, `queued_at` exists, and it is not failed.

**Regression:** `BookingReconciliationScenarioTest` verifies the actual delivery produced by the public HTTP booking path.

---

## Finding QA-BILLING-001 — Moyasar webhook authentication was fail-open when the secret was missing

**Area:** Billing / Moyasar webhooks

**Root cause:** `MoyasarWebhookProcessor` only verified the HMAC signature when `services.moyasar.webhook_secret` was non-empty. A missing secret therefore allowed processing to continue.

**Fix implemented:** Webhook authentication now fails closed. Missing secret or missing/invalid signature is rejected before JSON processing, ledger insertion, payment verification, subscription mutation, or other side effects.

**Regression:** missing secret, invalid signature, valid signature, duplicate event, successful `payment.paid` processing with verification, and processing failure/retry.

**Current status:** Fix and regression tests are on `main`; fresh MySQL CI evidence is required for certification.

---

## Finding QA-TESTINFRA-001 — Tenant test transaction rollback depended on the current default connection

**Area:** Tenant QA infrastructure

**Root cause:** `TenantTestCase` started a tenant transaction, but `tearDown()` could roll back whichever connection was current at teardown. Tests ending tenancy could therefore roll back the wrong connection and leak central fixture state.

**Fix implemented:** The test base records the actual tenant connection, explicitly rolls it back before ending tenancy, and keeps central rollback explicit through the configured central connection.

**Regression:** Tenant isolation tests exercise multiple methods without leaking the shared fixture tenant.

---

## Finding QA-ISOLATION-001 — Tenant token isolation needed an actual persisted Sanctum token model

**Area:** Tenant authorization tests

**Root cause:** The initial isolation test used the `NewAccessToken` wrapper incorrectly. The middleware checks `currentAccessToken()` and token abilities.

**Fix implemented:** Isolation tests use the persisted `PersonalAccessToken` model and verify same-tenant acceptance and cross-tenant rejection.

**Regression:** `TenantIsolationSecurityScenarioTest` and `TenantIsolationResourceScenarioTest`.

---

## Finding QA-SUPERADMIN-001 — Active tenant reconciliation must respect the Tenant model accessor contract

**Area:** Super Admin Dashboard / tenant aggregation

**Root cause:** The test initially counted only tenants with explicit `data.active=true`, while `Tenant::active` treats missing values as active by default.

**Fix:** Reconciliation uses the same active-state contract as the application.

**Regression:** `SuperAdminReconciliationScenarioTest` verifies total, active, paid, trial and recent tenants against central DB truth.

---

## Finding QA-REPORT-001 — Reports counted a different customer population than the Tenant Dashboard

**Area:** Reports / Customer metrics

**Root cause:** `ReportService::getStats()` counted `User::role('Customer')`, while canonical customers live in `customers` and are used by booking/Dashboard.

**Fix implemented:** `ReportService` uses the canonical `Customer` model.

**Regression:** `ReportingReconciliationScenarioTest` compares report customer totals with `Customer::count()` and the Tenant Dashboard metric.

---

## Finding QA-DELETION-001 — Tenant purge hardcoded the central connection

**Area:** Permanent tenant deletion

**Root cause:** `PermanentlyDeleteExpiredTenants` used a hardcoded `mysql` connection rather than the configured central connection.

**Fix implemented:** The command resolves and uses the configured central connection for central subscription/tenant operations.

**Regression:** `TenantDeletionSafetyScenarioTest` covers failed resource cleanup and successful cleanup.

**Current status:** Code fix and tests are on `main`; fresh MySQL CI evidence is required for certification.

---

## Finding QA-AUTH-001 — Tenant Staff/Assistant could mutate administrative configuration

**Area:** Tenant admin authorization / services / staff / settings

**Root cause:** The tenant admin route group admitted `Admin Tenant|Staff|Assistant`, while affected controllers had no method-level authorization and FormRequests returned `authorize() = true`. This allowed Staff/Assistant to reach service, schedule, staff and settings mutations.

**Fix implemented:** Added minimal role guards. `Admin Tenant` is required for service/schedule mutations, tenant settings writes, and staff create/update/delete. Read methods remain unchanged.

**Regression:** `AdminAuthorizationMatrixScenarioTest` and `AuthorizationMatrixExpandedScenarioTest` verify Staff/Assistant rejection and Tenant Admin success.

---

## Finding QA-BILLING-002 — Moyasar subscription activation could bypass the canonical central-connection contract

**Area:** Billing / Moyasar subscription activation

**Root cause:** `MoyasarService::activateSubscription()` used direct `DB::table()` calls for `tenant_subscriptions` and `subscription_plans`. The canonical models bind those records to `tenancy.database.central_connection`.

**Fix implemented:** The service now uses `TenantSubscription` and `SubscriptionPlan` for lookup/update. This enforces the central connection contract and fails explicitly when the tenant subscription is missing.

**Regression:** `MoyasarCentralConnectionScenarioTest` activates a subscription while tenant context is active and verifies the central subscription record.

---

## Expanded Authorization Coverage

`AuthorizationMatrixExpandedScenarioTest` covers:

- Staff cannot create/delete staff accounts.
- Assistant cannot create time slots.
- Assistant cannot change working-day configuration.
- Tenant Admin can create a time slot and staff account.

Queue/Appointment permissions are not changed by this guard because Staff/Assistant behavior there may be intentional and needs separate business-policy evidence.

---

## Test Infrastructure Policy

Every production defect discovered by Master QA must produce a regression test before the next feature family is accepted.

The test must validate the intended business outcome, not merely that an exception was thrown.

The canonical certification environment is MySQL. SQLite may be used for fast unit checks but is not sufficient evidence for tenant, locking, webhook, billing, concurrency, or certification gates.

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

The current `main` line contains the Master QA foundation, booking/appointment/queue/customer/notification coverage, Moyasar webhook hardening, tenant isolation test infrastructure, reporting/deletion safeguards, billing connection hardening, and authorization hardening. Fresh MySQL CI evidence is required for changes added after the last completed Master QA run.

Verified passing evidence from the earlier Master QA run on `a2e97f1` includes:

- Environment foundation
- Public booking golden flow
- Booking rules/negative cases
- Appointment lifecycle
- Queue lifecycle and business-date correctness
- Call-next locking/date scoping
- Customer/dashboard reconciliation
- Queue notification lifecycle and recovery basics
- Moyasar webhook security and payment-verification scenarios

Added/fixed afterward and awaiting fresh MySQL CI evidence:

- Tenant token isolation
- Tenant resource isolation
- Tenant test transaction connection safety
- Super Admin tenant/subscription reconciliation
- Reporting customer reconciliation
- Tenant deletion safety
- Expanded tenant authorization matrix
- Moyasar canonical central-connection activation

Next priority:

```text
Fresh MySQL CI on current main
→ Billing/Webhooks full reconciliation
→ Subscription access reconciliation
→ Full tenant/resource authorization matrix
→ Super Admin aggregation and revenue reconciliation
→ Reporting/export reconciliation
→ Deletion/cleanup safety
→ Browser smoke / final certification
```

Do not mark Velora production-certified merely because the existing global PHPUnit suite is green. Certification requires the master scenario suite, reconciliation, security, concurrency and billing gates above.
