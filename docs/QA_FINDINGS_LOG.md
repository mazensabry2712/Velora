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

## Finding QA-TESTINFRA-002 — Full test suite depended on a tracked local `.env` existing physically

**Area:** PHPUnit/local and CI test bootstrap

**Evidence:** A local `php artisan test` run after removing `.env` produced widespread failures/warnings across payment, repository, admin, booking, localization, health, and design-system tests with `file_get_contents(.../.env): Failed to open stream`. HTTP tests then cascaded into `MissingAppKeyException`, and Symfony's HTML error renderer exhausted the 128 MB PHP memory limit while rendering repeated exception payloads.

**Root cause:** `.env` was correctly removed from Git for security, but legacy tests directly inspect the physical `.env` file. `phpunit.xml` previously bootstrapped only `vendor/autoload.php`, so a fresh checkout without a local `.env` did not satisfy the legacy test contract.

**Fix implemented:** PHPUnit now bootstraps through `tests/bootstrap.php`. When `.env` is missing, the bootstrap creates a temporary local `.env` from `.env.example`, changes the environment to testing, injects a throwaway `APP_KEY`, and removes the generated file at process shutdown. An existing developer `.env` is never overwritten.

**Security impact:** `.env` remains ignored by Git (`.env`, `.env.*`, with only `.env.example` allowed). No real secret is added to source control.

**Regression:** `Tests\Unit\TestEnvironmentBootstrapTest` verifies the generated testing environment has a non-empty testing `APP_KEY` and verifies the repository continues to ignore `.env` while allowing `.env.example`.

**Current status:** Fix and regression test are on `main`; fresh CI evidence is required.

---

## Finding QA-TESTINFRA-003 — SQLite `:memory:` database was incompatible with Laravel test application lifecycle

**Area:** PHPUnit database isolation / Tenancy

**Evidence:** Running the QA suite sequentially after the `.env` bootstrap remediation produced 46 failures and 13 passes. Most failures shared the exact signatures:

```text
no such table: tenants
no such table: subscription_plans
```

The failing statements came from multiple unrelated test classes, proving the common failure happened before their business assertions.

**Root cause:** `phpunit.xml` configured SQLite as `:memory:` while `TenantTestCase` cached migration completion per test class. Laravel can boot a fresh application/connection while the static migration flag survives. A fresh in-memory SQLite connection starts empty, so the next test attempted to use a schema that no longer existed.

**Fix implemented:** `tests/bootstrap.php` now creates a SQLite file unique to the PHPUnit process using `TEST_TOKEN`, `PARALLEL_PROCESS`, or the PHP process ID. The file is removed after the process ends. This preserves the schema across Laravel application boots within one worker while keeping parallel workers isolated from each other.

`tests/TestCase.php` also ensures the configured central connection has a migration table and runs application migrations when a fresh test database is detected.

**Regression:** `Tests\Unit\TestEnvironmentBootstrapTest` verifies `DB_DATABASE` points to the process-isolated SQLite file and that the file exists during the test process.

**Operational impact:** The previous `php artisan test --parallel --processes=12` setup could not safely use shared `:memory:` SQLite. After this remediation, parallel execution has isolated test files per worker and can be re-evaluated. MySQL remains the certification environment.

---

## Finding QA-REPORT-002 — Staff performance report used SQLite-incompatible HAVING semantics

**Area:** Reports / staff performance

**Observed:**

```text
SQLSTATE[HY000]: General error: 1
HAVING clause on a non-aggregate query
```

**Root cause:** `ReportService::getStaffPerformance()` filtered the `withCount()` alias using `HAVING staff_appointments_count > 0`. MySQL accepts this query shape, while SQLite rejects it without an aggregate/grouping context.

**Fix implemented:** Preserve `withCount()` for the displayed count and use `whereHas('staffAppointments', ...)` with the same status/date filters to express the existence condition portably.

**Regression:** `ReportingReconciliationScenarioTest` continues to exercise report generation and canonical customer reconciliation; the cross-database query shape is covered by the QA suite on SQLite and MySQL CI.

---

## Finding QA-BILLING-003 — Moyasar attempted to persist unsupported `billing_cycle` column on tenant_subscriptions

**Area:** Billing / Moyasar subscription activation

**Observed:**

```text
no such column: billing_cycle
```

**Root cause:** `MoyasarService::activateSubscription()` attempted to write `billing_cycle` into `tenant_subscriptions`, but the canonical subscription schema stores `billing_cycle` on `subscription_plans`. The service already reads that plan field to determine duration.

**Fix implemented:** Removed the unsupported duplicate subscription write. `SubscriptionPlan::billing_cycle` remains the source of truth, while `TenantSubscription` stores the resulting active period and payment state.

**Regression:** `MoyasarCentralConnectionScenarioTest` verifies activation while tenant context is active and checks the central subscription record.

---

## Finding QA-BOOK-002 — Holiday availability comparison was too strict for a calendar-date field

**Area:** Booking availability / public booking

**Observed:** `BookingAvailabilityRulesScenarioTest::holiday_makes_the_staff_unavailable_even_when_working_hours_exist()` persisted an all-staff holiday successfully, but `SlotEngine::validateSlot()` returned an available result for the same calendar day.

**Root cause:** The Holiday model casts `date` as a date, while `SlotEngine::isHoliday()` compared the database value with exact equality using `where('date', $date->toDateString())`. On the test schema the persisted date representation can contain a time component, so exact equality did not match even though `whereDate()` correctly identified the row.

**Fix implemented:** `SlotEngine::isHoliday()` now uses `whereDate('date', $date->toDateString())`, preserving the business contract as calendar-date matching and remaining compatible with the existing Holiday model/query style.

**Regression:** `BookingAvailabilityRulesScenarioTest` now proves the Holiday fixture exists, proves `SlotEngine` returns `holiday`, and then verifies `CreatePublicBooking` rejects the booking with `SlotUnavailableException('holiday')`.

**Status:** Fix committed to `main` as `cfc9e468a3b65c13d3c11ec7aec0c6381a555cc2`. Focused regression must pass locally and then receive fresh MySQL CI evidence.

---

## Finding QA-REPORT-003 — Tenant Dashboard daily appointment metrics disagreed with canonical database truth

**Area:** Tenant Dashboard / appointment metrics

**Observed:** `MasterBusinessFlowScenarioTest::dashboard_reconciles_exactly_with_database_truth_for_the_golden_dataset()` persisted one confirmed appointment for the current business date. The canonical `whereDate('date', $today)` query returned `1`, while Dashboard `stats['confirmed']` returned `0`.

**Root cause:** `DashboardController` calculated `total_today`, `completed_today`, and `confirmed_today` inside a raw aggregate using exact `date = ?` comparison. The application's canonical appointment contract and dashboard collections use date-aware comparisons, so the aggregate could disagree when the stored date representation included a time component.

**Fix implemented:** The three calendar-day aggregate predicates now use `DATE(date) = ?`, making the projection use the same calendar-date semantics as the canonical reconciliation queries without changing the metric's intended meaning or the aggregate structure.

**Regression:** `MasterBusinessFlowScenarioTest` reconciles `stats['confirmed']`, `stats['queue']`, today's appointments, status distribution, top services, and invoice creation against direct database truth.

**Status:** Fix committed to `main` as `c033fb8fd4628dad7cda5e569ccc7073500b27bf`. Focused regression must pass locally and then receive fresh MySQL CI evidence.

---

## Test Infrastructure Policy

Every production defect discovered by Master QA must produce a regression test before the next feature family is accepted.

The test must validate the intended business outcome, not merely that an exception was thrown.

The canonical certification environment is MySQL. SQLite may be used for fast local checks, but tenant, locking, billing, webhook, concurrency, and final certification gates require MySQL evidence.

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

The current `main` line contains the Master QA foundation, booking/appointment/queue/customer/notification coverage, Moyasar webhook hardening, tenant isolation test infrastructure, reporting/deletion safeguards, billing connection hardening, authorization hardening, local test bootstrap remediation, and the latest booking/dashboard reconciliation fixes. Fresh MySQL CI evidence is required for the new commits.

Next priority:

```text
Fresh MySQL CI on current main
→ verify holiday enforcement regression
→ verify dashboard date reconciliation regression
→ close any remaining Master QA failures
→ Billing/Webhooks full reconciliation
→ Subscription access reconciliation
→ Full tenant/resource authorization matrix
→ Super Admin aggregation and revenue reconciliation
→ Reporting/export reconciliation
→ Deletion/cleanup safety
→ Browser smoke / final certification
```

Do not mark Velora production-certified merely because a local SQLite run is green. Certification requires the master scenario suite, MySQL evidence, reconciliation, security, concurrency and billing gates above.
