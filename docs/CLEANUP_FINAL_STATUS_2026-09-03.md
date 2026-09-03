# Velora — Cleanup Final Status — 2026-09-03

## Implemented

- Staff/service identity consolidated on `staff_services.staff_id`.
- Staff scheduling consolidated on `StaffWorkingHours` / `staff_working_hours`.
- Customer business identity separated from User authentication identity through optional `customers.user_id` account linkage.
- Invoice customer identity reconciled to `customers`.
- Appointment runtime ownership moved to `customer_id_new` and `staff_id_new`.
- Booking creation, recurring generation, Admin booking, direct queue entry and appointment repository no longer write the old appointment User IDs.
- User appointment/invoice access now traverses Customer/Staff business entities.
- Queue customer access now resolves exclusively through the canonical Appointment → Customer relationship; tenant queue lookup no longer reads `appointments.customer_id`.
- Queue VIP state is derived from the canonical Customer business state; the `Customer::is_vip` accessor preserves legacy linked-User reads without adding a duplicate Customer column.
- Queue lifecycle notification observer no longer references duplicate `newCustomer` relationships or User-owned customer identity.
- Public queue status now loads only canonical Appointment → Customer/Staff relations.
- Appointment reminder discovery, mail and job delivery are Customer-only and use canonical `starts_at`, `customer` and `staff` relationships; the obsolete User/newCustomer/newStaff compatibility path was removed.
- Tenant test infrastructure now creates a canonical Customer fixture linked to the existing authentication User while retaining the User fixture for authentication-oriented tests.
- Appointment date-oriented scopes/helpers now use canonical `starts_at` instead of the legacy `date` field for runtime calculations.
- Tenant-isolation and RBAC test fixtures were aligned with the canonical model/package boundaries.
- Appointment migration `2026_09_03_000006_reconcile_appointment_identity.php` backfills historical customer/staff references and fails closed on unresolved mappings.
- Application/test role usage now targets `Spatie\\Permission\\Models\\Role` directly; the legacy `App\\Models\\Role` compatibility wrapper was removed.
- Analytics aggregation now reads canonical appointment timestamps and `customer_id_new` instead of the legacy appointment identity/date fields.
- `EloquentAppointmentReader` was corrected to eager-load the canonical `Appointment::staff` relation; the stale `staffNew` relation reference was removed.

## Intentionally deferred

- Physical removal/renaming of legacy appointment columns until fresh MySQL migration + full regression verifies zero remaining runtime dependency.
- `spatie/laravel-translatable` migration until package installation, data migration, model refactor and regression coverage can land together.
- `spatie/laravel-activitylog` migration until audit-data preservation and consumer migration can land together.
- Maintained FCM channel migration until credentials/configuration and delivery tests can be migrated together.
- Media-library migration until the current file/image subsystem is confirmed as a true replacement candidate.

## Verification rule

Current `main` is not certified green by test count alone. Remote GitHub Actions status and, when available, a fresh local run are required before release certification.

The latest cleanup pushes triggered or are expected to trigger the repository's MySQL-backed tests, quality checks and Master QA workflows. At the latest API check for the corrective reader commit, no combined status checks were yet reported. This status remains deliberately conservative and does not treat missing checks as a pass.

A concrete runtime defect found during the current review was fixed in commit `cfea0723c263784ed071cf68620323c44f5948ae`: `EloquentAppointmentReader::forCustomer()` now loads `staff` instead of the removed `staffNew` relation.

Expected local verification:

```powershell
git pull --ff-only origin main
php artisan optimize:clear
php artisan migrate
php artisan tenants:migrate --force
php artisan test --compact
vendor/bin/pint --test
composer validate --strict
```
