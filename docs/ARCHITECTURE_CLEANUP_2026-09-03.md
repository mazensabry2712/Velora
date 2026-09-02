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

### Invoice identity

- A reconciliation migration converts the historical `invoices.customer_id -> users` identity to the canonical `invoices.customer_id -> customers` identity expected by the current `Invoice` model.
- The migration stages the mapping, aborts on unresolved rows, changes the FK, then removes the temporary staging column.

### Public booking staff-service resolution

- Removed the legacy fallback through `user.services`.
- Availability now resolves staff/service assignment through `staff_services.staff_id` only.

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

## Future Appointment identity migration

The repository still has a migration-era pair of columns:

```text
appointments.customer_id_new -> customers
appointments.staff_id_new    -> staff
```

and historical columns:

```text
appointments.customer_id -> users
appointments.staff_id    -> users
```

These must not be blindly dropped. The final migration will first reconcile all legacy rows, convert remaining runtime consumers, add the final `customers` / `staff` FKs, then rename the canonical columns to the stable domain names `customer_id` and `staff_id`.

## Future architecture alignment

The target remains a Laravel modular monolith:

```text
Platform Core
  Tenancy / Auth / RBAC / Billing / Localization
  Notifications / Audit / Settings / Shared infrastructure

Business Modules
  Customer / Staff / Booking / Queue / Reports
  Future: CRM / HR / Inventory / Sales / Finance / POS / Projects
```

The database follows the same ownership model: each domain concept has one canonical owner and migrations are used as controlled evolution steps rather than as competing schema definitions.

## Verification status

The current `main` branch has CI runs triggered for the cleanup commits. Their execution state must be checked from GitHub Actions before declaring the branch green; no local test result is claimed by this document.
