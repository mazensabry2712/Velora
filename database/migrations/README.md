# Velora Database Migrations

## Purpose

This directory contains the Laravel central-database migration history. Tenant database migrations live under `database/migrations/tenant/` and are executed by Stancl Tenancy.

## Non-negotiable rules

1. **Never rewrite or rename an already-deployed migration.** Historical migration files are part of the database migration ledger.
2. **Forward migrations only.** Fixing an old schema means adding a new migration that transforms the deployed state safely.
3. **Separate schema change from data reconciliation when practical.** Large backfills should be explicit, resumable where possible, and guarded against unresolved data.
4. **Fail closed on destructive reconciliation.** A migration that would drop data or an identity column must throw when unresolved rows remain.
5. **Make migrations environment-safe.** Use Laravel Schema/DB abstractions unless the operation is intentionally driver-specific. Driver-specific SQL must be isolated and documented.
6. **Keep migration order chronological.** Laravel and the tenant migration runner use the migration filename timestamp/order; do not rely on folder names to encode execution order.
7. **Name new migrations by domain and intent.** Preferred format:
   `YYYY_MM_DD_HHMMSS_<module>_<action>_<target>.php`

Examples:

- `2026_09_10_120000_booking_add_booking_source_to_appointments.php`
- `2026_09_10_121000_customer_link_user_accounts.php`
- `2026_09_10_122000_core_add_platform_setting.php`

## Tenant migration organization

The current application intentionally keeps tenant migrations in one scanner path:

```text
 database/migrations/tenant/
```

Do **not** move existing migration files into module subdirectories. The deployed migration history must remain stable, and Stancl's tenant migration configuration currently points at this directory.

Future migrations should use the domain prefix in the filename instead of physical subfolders. This gives us module ownership without risking migration discovery/order changes.

## Migration ownership map

```text
Core / Platform
  users, roles, permissions, tenant settings, shared platform infrastructure

Customer
  customers, GDPR, customer lifecycle, customer/account linkage

Staff
  staff, working hours, breaks, time-off, commissions

Booking
  services, appointments, availability, recurring bookings

Queue
  queue entries, queue lifecycle state, queue notification delivery

Billing
  invoices, payment transactions, subscription-related tenant data

Notifications
  notification delivery state, device/push tokens and channel metadata

Reports / Analytics
  materialized/reporting tables only when a real persistence requirement exists
```

## Large migrations

For identity or schema reconciliations use this sequence:

```text
1. Add canonical column/table
2. Backfill from legacy source
3. Detect unresolved/ambiguous rows
4. Add constraints/indexes only after valid data exists
5. Convert application writes to canonical source
6. Deploy verification/regression tests
7. Remove legacy column/table in a later migration
8. Rename canonical field to the final domain name only after consumers are migrated
```

This is the strategy used for the Staff/Service pivot and the planned Appointment identity reconciliation.

## Compatibility policy

Historical migrations may contain obsolete columns or implementation details. That is acceptable. Runtime application code must not depend on those fields once a canonical replacement is established, except in an explicitly documented compatibility layer.

## Verification

Every migration change must be validated with:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan tenants:migrate --force
php artisan test --compact
```

CI must also execute the migration path on MySQL before a cleanup is considered complete.
