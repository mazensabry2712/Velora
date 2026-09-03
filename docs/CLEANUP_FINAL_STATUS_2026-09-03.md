# Velora — Cleanup Final Status — 2026-09-03

## Implemented

- Staff/service identity consolidated on `staff_services.staff_id`.
- Staff scheduling consolidated on `StaffWorkingHours` / `staff_working_hours`.
- Customer business identity separated from User authentication identity through optional `customers.user_id` account linkage.
- Invoice customer identity reconciled to `customers`.
- Appointment runtime ownership moved to `customer_id_new` and `staff_id_new`.
- Booking creation, recurring generation, Admin booking, direct queue entry and appointment repository no longer write the old appointment User IDs.
- User appointment/invoice access now traverses Customer/Staff business entities.
- Tenant-isolation and RBAC test fixtures were aligned with the canonical model/package boundaries.
- Appointment migration `2026_09_03_000006_reconcile_appointment_identity.php` now backfills historical customer/staff references and fails closed on unresolved mappings.

## Intentionally deferred

- Physical removal/renaming of legacy appointment columns until fresh MySQL migration + full regression verifies zero remaining runtime dependency.
- `spatie/laravel-translatable` migration until package installation, data migration, model refactor and regression coverage can land together.
- `spatie/laravel-activitylog` migration until audit-data preservation and consumer migration can land together.
- Maintained FCM channel migration until credentials/configuration and delivery tests can be migrated together.
- Media-library migration until the current file/image subsystem is confirmed as a true replacement candidate.
- Deletion of `App\\Models\\Role` compatibility wrapper until fresh full-suite verification confirms no remaining dependency.

## Verification rule

Current `main` is not certified green by test count alone. Remote GitHub Actions status and, when available, a fresh local run are required before release certification.

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
