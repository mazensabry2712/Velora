# Velora — Project Status

> **Snapshot:** Main branch reviewed and updated on 2026-09-03.
>
> This document separates completed architecture work from work that still requires fresh verification or a deliberate future migration.

## Product

Velora is a multi-tenant appointment-booking SaaS for businesses needing online booking, staff scheduling, queue management, customer management, reports, administration, localization and subscription billing.

## Current architecture

- Laravel 12 / PHP 8.2+.
- Blade + Vite + Tailwind CSS 4.
- Stancl Tenancy v3 for tenant isolation/lifecycle.
- Laravel Sanctum for API authentication/abilities.
- Spatie Permission for RBAC.
- Stripe / Moyasar billing integrations.
- MySQL tenant databases with queued/scheduled operations.
- Modular-monolith direction documented in `docs/FUTURE_ARCHITECTURE.md`.

## Completed cleanup

### Identity ownership

- `Customer` is the business customer entity.
- `Staff` is the business staff entity.
- `User` is the authentication/system identity and may optionally link to Customer or Staff.
- `customers.user_id` is an account link, not the source of appointment ownership.

### Staff and services

- `staff_services.staff_id` is the only runtime staff identity.
- Legacy `staff_services.user_id` is removed by migration after backfill/deduplication.
- Staff scheduling is canonical on `StaffWorkingHours` / `staff_working_hours`.
- The legacy `StaffSchedule` runtime path was removed.

### Appointments

- Runtime appointment ownership is canonical on `customer_id_new -> customers` and `staff_id_new -> staff` during the migration window.
- `Appointment::customer()` and `Appointment::staff()` use those canonical relationships.
- Booking creation, recurring creation, Admin booking, direct queue entry and appointment repository filters no longer write the old User-owned appointment IDs.
- `User::appointments()` and `User::staffAppointments()` traverse Customer/Staff instead of directly owning appointments.
- Migration `2026_09_03_000006_reconcile_appointment_identity.php` backfills historical appointment identities and fails closed when a legacy customer/staff mapping cannot be resolved.

### Billing

- `invoices.customer_id` is canonical on `customers` after reconciliation.
- `PaymentTransaction::customer()` is based on the Customer entity.

### Canonical infrastructure

- Laravel named `RateLimiter` is canonical for HTTP throttling.
- Sanctum `CheckAbilities` is canonical for token abilities.
- Stancl tenancy middleware is canonical for domain tenancy initialization.
- Spatie Permission is canonical for roles/permissions, with direct use of the package Role model and no application Role wrapper.
- NielsNumbers Localizer is canonical for platform locale routing.

## Documentation

Architecture and cleanup decisions are documented in:

- `docs/FUTURE_ARCHITECTURE.md`
- `docs/ARCHITECTURE_CLEANUP.md`
- `docs/ARCHITECTURE_CLEANUP_2026-09-03.md`
- `docs/CLEANUP_FINAL_STATUS_2026-09-03.md`
- `database/migrations/README.md`
- `database/migrations/tenant/README.md`
- `docs/CHANGELOG_IMPLEMENTATION.md`

## Verification status

The historical baseline confirmed before Queue Lifecycle implementation was **570 tests / 5624 assertions / 0 failures / 0 errors**. That baseline is not certification for the later identity/schema refactors.

Fresh local testing is not claimed from the remote repository. The current remote `main` has no completed workflow result available yet for certification; a fresh GitHub Actions run and, when available, a fresh local run are required before calling the branch green.

## Remaining release-risk work

### P0 / P1

- Fresh MySQL migration and full regression of the current identity/schema changes.
- Complete tenant isolation review for every ID-based endpoint and explicit central/tenant database access.
- Verify billing webhooks are the only payment-state authority and are idempotent.
- Complete object/resource-level authorization review.
- Complete real storage usage tracking for subscription quotas.
- Verify public/login/queue/billing rate limits.
- Review high-volume tenant indexes and query performance.
- Complete browser/mobile/RTL QA and real provider delivery checks.

## Deliberately deferred package migrations

These are not partially installed:

- `spatie/laravel-translatable` — needs data migration, model refactor and regression coverage.
- `spatie/laravel-activitylog` — needs audit-data preservation and consumer migration.
- Maintained FCM notification channel — needs provider credentials/configuration, notification refactor and delivery tests.
- `spatie/laravel-medialibrary` — only after confirming the current media subsystem is a true replacement candidate.

## Remaining appointment schema finalization

After fresh MySQL verification proves the old appointment IDs are unused at runtime:

```text
remove legacy appointment foreign keys/columns
→ rename customer_id_new → customer_id
→ rename staff_id_new → staff_id
→ rebuild canonical indexes/constraints
→ update tests/consumers
→ run Master QA
```

Deployed historical migrations remain immutable.

## Local release verification

```powershell
git pull --ff-only origin main
php artisan optimize:clear
php artisan migrate
php artisan tenants:migrate --force
php artisan test --compact
vendor/bin/pint --test
composer validate --strict
```
