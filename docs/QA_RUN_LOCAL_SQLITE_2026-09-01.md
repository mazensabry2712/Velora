# Velora QA — Historical Local SQLite Run Diagnostic (2026-09-01)

> **Historical document — superseded by the current MySQL test contract.**
>
> This report preserves the original 2026-09-01 SQLite investigation and its findings. It is not the current PHPUnit or CI environment. The active certification contract is **MySQL 8.4 + PHP 8.4**.

## Historical trigger

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

The failures were concentrated around tenant bootstrap and central billing fixtures, indicating a shared historical test-environment issue rather than dozens of independent product defects.

## Historical root causes

### Root Cause A — SQLite `:memory:` was incompatible with the then-current test bootstrap lifecycle

The historical `phpunit.xml` configured SQLite as `:memory:` and the old test lifecycle cached migration state across fresh application/connection boots. A new in-memory connection could therefore start empty while the cached migration flag remained true.

### Root Cause B — Central QA tests did not guarantee a central schema before use

Several QA tests extended the generic test case and queried central models/tables without guaranteeing a fresh central schema.

### Root Cause C — Billing activation wrote a column not present in the canonical subscription schema

`MoyasarService::activateSubscription()` attempted to write `billing_cycle` to `tenant_subscriptions`. The canonical source of truth is `SubscriptionPlan::billing_cycle`.

### Root Cause D — Staff reporting relied on a SQLite-incompatible query shape

`ReportService::getStaffPerformance()` used a `HAVING` clause against a `withCount()` alias. The business meaning was preserved while the existence filter was moved to `whereHas(...)`.

## Remediation that resulted from the historical investigation

The investigation led to these durable fixes:

- Central schema bootstrap was made explicit for fresh test applications.
- `MoyasarService::activateSubscription()` stopped persisting the unsupported duplicate `billing_cycle` column.
- Staff reporting was rewritten to avoid the SQLite-specific `HAVING` failure mode.
- Tenant transaction rollback records the actual tenant connection explicitly.
- Tenant isolation coverage uses persisted Sanctum token records.
- Subsequent QA fixes also normalized holiday and dashboard calendar-date comparisons.

## Current environment superseding this report

The active repository contract is now:

```text
Application DB: MySQL
Central tenancy DB: MySQL
CI: MySQL 8.4
PHP: 8.4
PHPUnit: MySQL-driven via the CI/local environment
Queue during tests: sync
Mail during tests: array
Cache during tests: array
Session during tests: array
```

Current `config/database.php` defaults to MySQL and only defines the MySQL application connection. Current `phpunit.xml` no longer hard-codes `DB_CONNECTION=sqlite` or a SQLite database path.

The current `tests/bootstrap.php` no longer creates a SQLite test database. It creates a temporary `.env` only when needed, injects a throwaway `APP_KEY`, and leaves database selection to the active environment contract.

## Certification rule

This historical local SQLite run was never final certification evidence. Final certification requires fresh MySQL evidence, including Master QA, broader regression, authorization/tenant isolation, billing/webhook verification, concurrency-sensitive behavior, and reconciliation checks.

## Historical value

Keep this report for auditability and root-cause history. Do not copy its old SQLite bootstrap instructions into current runbooks or use them as the definition of the current test environment.
