# Velora QA — Local SQLite Run Diagnostic (2026-09-01)

## Trigger

Developer ran:

```text
php artisan test tests/Feature/QA --compact
```

Observed:

```text
Tests: 46 failed, 13 passed
Duration: 21.53s
```

A prior parallel full-suite attempt produced hundreds of errors. The repeated error signatures were:

```text
SQLSTATE[HY000]: General error: 1 no such table: tenants
SQLSTATE[HY000]: General error: 1 no such table: subscription_plans
```

The failures were concentrated around tenant bootstrap and central billing fixtures, indicating a shared test-environment issue rather than dozens of independent product defects.

## Root Cause A — SQLite `:memory:` is incompatible with the current test bootstrap lifecycle

`phpunit.xml` previously configured:

```text
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
TENANCY_CENTRAL_CONNECTION=sqlite
```

`TenantTestCase` cached the fact that migrations had completed for a test class in static state. Laravel application/connection lifecycle can create a fresh SQLite in-memory database while that static flag remains true. The next test then reused the flag and attempted to insert into `tenants` on an empty database.

This explains the repeated:

```text
no such table: tenants
```

and the same mechanism explains missing central tables such as `subscription_plans`.

## Root Cause B — Central QA tests did not guarantee a central schema before use

Several QA tests extend the generic `Tests\TestCase` and directly query central models/tables. The base test case previously did not ensure the central migration schema existed for a fresh test application.

That made tests such as Super Admin reconciliation and deletion depend on test order or a prior migration side effect.

## Root Cause C — Billing activation wrote a column not present in the canonical subscription schema

`MoyasarService::activateSubscription()` attempted to write:

```text
billing_cycle
```

to `tenant_subscriptions`.

The canonical `tenant_subscriptions` creation migration does not define that column. `billing_cycle` belongs to `subscription_plans`, and the service already reads it from the plan to determine the duration.

Fix: stop persisting the unsupported duplicate `billing_cycle` field on the subscription record. The source of truth remains `SubscriptionPlan::billing_cycle`.

## Root Cause D — SQLite portability issue in staff reporting

`ReportService::getStaffPerformance()` used a `HAVING` clause against the `withCount()` alias:

```text
having staff_appointments_count > 0
```

SQLite rejected this shape with:

```text
HAVING clause on a non-aggregate query
```

The fix keeps the same business meaning but moves the existence filter to `whereHas('staffAppointments', ...)` while retaining `withCount()` for the displayed count.

## Remediation Implemented

### Test database isolation

`tests/bootstrap.php` now:

1. Keeps the real `.env` out of Git.
2. Generates a temporary testing `.env` only when a local `.env` is absent.
3. Injects a throwaway testing `APP_KEY`.
4. Creates a SQLite file named from `TEST_TOKEN`, `PARALLEL_PROCESS`, or the current process ID.
5. Removes the generated SQLite file at process shutdown.

Therefore:

```text
sequential process → database/testing_<process>.sqlite
parallel process A → database/testing_<tokenA>.sqlite
parallel process B → database/testing_<tokenB>.sqlite
```

This preserves database state across Laravel application boots inside one process and isolates parallel workers from one another.

### Central schema bootstrap

`tests/TestCase.php` now checks the configured central connection for the migrations table and runs the application migrations when the central schema is not yet initialized.

This removes test-order dependence for central-only tests.

### QA fixtures

Super Admin billing/reconciliation and tenant deletion tests now create a minimal disposable subscription plan when the central database has no plan rows.

This makes those scenarios self-contained and independent of seed order.

### Reporting portability

`ReportService::getStaffPerformance()` no longer relies on SQLite-incompatible `HAVING` semantics for the `withCount()` alias.

### Billing schema contract

`MoyasarService::activateSubscription()` no longer writes `billing_cycle` to `tenant_subscriptions`.

## Regression Coverage

`tests/Unit/TestEnvironmentBootstrapTest.php` now verifies:

```text
.env exists for tests
APP_ENV=testing
APP_KEY is generated
DB_DATABASE points to a process-isolated SQLite file
SQLite file exists
.env remains ignored
.env.example remains allowed
SQLite test database files remain ignored
```

## Important Classification

The repeated `no such table` errors are classified primarily as **test infrastructure defects**, not as 40+ independent product failures.

The following are separate production/schema compatibility findings exposed by the run:

- unsupported `tenant_subscriptions.billing_cycle` write
- SQLite-incompatible reporting query shape

Both received minimal fixes and regression coverage.

## Evidence Standard

The local SQLite run is useful for fast feedback, but it is not the final certification environment.

Certification still requires:

```text
MySQL 8.4
PHP 8.4
Master QA suite
Security / tenant isolation
Authorization
Billing / webhook verification
Concurrency
Reconciliation
Final regression
```

## Next Commands

After pulling the current `main`:

```text
php artisan test tests/Unit/TestEnvironmentBootstrapTest.php
php artisan test tests/Feature/QA --compact
php artisan test --parallel --processes=12
php artisan test
```

Interpret the parallel run only after confirming that the process-isolated SQLite bootstrap is active. The final release decision still comes from the MySQL certification workflow.
