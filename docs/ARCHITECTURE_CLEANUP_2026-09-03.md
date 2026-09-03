# Velora Architecture Cleanup — 2026-09-03

## Completed in this cleanup pass

### Staff identity

- `staff_services` is canonical on `staff_id`.
- Legacy `user_id` pivot rows are backfilled and deduplicated.
- A final migration removes `staff_services.user_id` after verifying no unresolved rows remain.
- Runtime staff/service code now uses the `Staff` relationship.

### Staff scheduling

- `StaffWorkingHours` / `staff_working_hours` is the canonical staff availability representation.
- The old `StaffSchedule` model/table path was removed from runtime application code.

### Customer identity

- `Customer` is the booking-facing business entity.
- `customers.user_id` was added as an optional account link for authenticated customers.
- Existing customer records are linked to matching user accounts by email when the match is unambiguous.
- `User::customerProfile()` and customer-owned appointment/invoice traversal make the authentication/business-identity boundary explicit.

### Appointment identity

- `Appointment` now exposes `customer()` through `customer_id_new -> customers` and `staff()` through `staff_id_new -> staff`.
- `Appointment::$fillable` no longer accepts legacy `customer_id` or `staff_id`, preventing new runtime writes to the user-owned appointment identity.
- Public/admin booking creation, recurring booking generation and direct queue entry now write only canonical customer/staff identifiers.
- `AppointmentRepository` filters and searches against `customer_id_new` / `staff_id_new` and the Customer entity's own name/contact fields.
- `User::appointments()` and `User::staffAppointments()` now traverse the business Customer/Staff entities instead of directly owning appointment records.
- A forward migration `2026_09_03_000006_reconcile_appointment_identity.php` backfills historical appointment IDs into the canonical entities, materializes missing canonical time fields where possible, and fails closed if legacy customer/staff rows remain unresolved.
- Legacy appointment columns remain physically present for the migration window; they are no longer the runtime authority.

### Invoice identity

- A reconciliation migration converts the historical `invoices.customer_id -> users` identity to the canonical `invoices.customer_id -> customers` identity expected by the current `Invoice` model.
- The migration stages the mapping, aborts on unresolved rows, changes the FK, then removes the temporary staging column.

### Queue identity

- Direct queue entry no longer creates a synthetic User customer or writes legacy appointment IDs.
- Queue-created appointments use `customer_id_new` and `staff_id_new`.

### Admin appointment identity

- Admin appointment creation/update now operates on Customer and Staff business entities.
- Admin staff validation targets `staff.id` rather than the User identity.

### RBAC test compatibility

- Current test fixtures that still imported `App\\Models\\Role` were moved to `Spatie\\Permission\\Models\\Role` directly.
- `config/permission.php` already uses Spatie's package Role model as its source of truth.
- The compatibility `App\\Models\\Role` wrapper is retained until a fresh full-suite run proves no remaining dynamic/string-based dependency exists.

## Migration organization decision

The migration tree is intentionally **chronological and immutable**.

- Existing deployed migration filenames are not renamed or moved.
- Central migrations remain under `database/migrations`.
- Tenant migrations remain under `database/migrations/tenant` because Stancl Tenancy is configured to scan that path.
- Future module ownership is expressed through filenames such as `<timestamp>_booking_<intent>.php`, not nested folders.
- Large schema reconciliations follow:

```text
add canonical representation
→ backfill
→ validate / fail closed
→ switch runtime
→ add constraints
→ remove legacy representation
→ rename to final domain name when all consumers are ready
```

Detailed rules are in:

- `database/migrations/README.md`
- `database/migrations/tenant/README.md`
- `docs/FUTURE_ARCHITECTURE.md`

## Deliberately not half-migrated

The following package migrations remain intentionally deferred until they can land with Composer lockfile changes, data migrations, refactors and regression coverage in the same release:

- `spatie/laravel-translatable` for model translation storage.
- `spatie/laravel-activitylog` for audit/activity storage.
- A maintained Laravel FCM notification channel plus its Firebase credentials/configuration.
- `spatie/laravel-medialibrary` for file/media lifecycle.

The existing custom implementations remain the active source for those responsibilities until their replacement can be verified end-to-end.

## Future canonical cleanup

After a fresh MySQL migration run and full regression pass confirm zero legacy appointment dependencies, the next schema step is:

```text
validate appointments.customer_id / staff_id are migration-only
→ remove legacy appointment foreign keys/columns
→ rename customer_id_new → customer_id
→ rename staff_id_new → staff_id
→ update indexes/constraints
→ run full Master QA
```

No physical rename is performed prematurely because the repository has historically exposed the old column names and deployed migration history is immutable.

## Verification status

The current `main` branch contains the cleanup commits through the direct-to-main identity refactor. GitHub Actions must be checked against the current `main` SHA before the branch is called green; this document does not claim an unverified local or remote test result.
