# Velora Tenant Migrations

Tenant migrations run against each tenant database through Stancl Tenancy. The canonical migration path is configured in `config/tenancy.php` as `database/migrations/tenant`.

## Rules

- Keep all already-deployed migration filenames unchanged.
- Add new migrations chronologically; never edit history to make the current schema look cleaner.
- Prefer Laravel's `Schema` and query builder APIs so SQLite-based tests and MySQL production remain compatible.
- A migration may use driver-specific SQL only when the feature itself is driver-specific; isolate it behind a driver check or a safe `try/catch` and document the reason.
- Do not place business logic in migrations beyond deterministic data reconciliation required to move from one schema state to another.
- Data backfills must be idempotent and must not silently discard ambiguous records.
- Before dropping a legacy column, prove that the canonical column is populated and that application consumers no longer read/write the old field.
- Add unique constraints after reconciliation, not before, when legacy duplicates are possible.

## Future module naming

Physical subfolders are intentionally avoided for now because the tenant migration runner is configured around one canonical path. Module ownership is expressed in filenames instead:

```text
<timestamp>_core_<intent>.php
<timestamp>_customer_<intent>.php
<timestamp>_staff_<intent>.php
<timestamp>_booking_<intent>.php
<timestamp>_queue_<intent>.php
<timestamp>_billing_<intent>.php
<timestamp>_notifications_<intent>.php
```

This supports the future modular-monolith architecture without changing migration discovery.

## Current canonical domains

```text
Platform Core  -> tenant identity, permissions, shared settings/infrastructure
Customer       -> customer profile, lifecycle, GDPR, customer account linkage
Staff          -> staff identity, services, working hours, breaks, time-off
Booking        -> services, appointments, availability, recurring bookings
Queue          -> waiting-room state and queue lifecycle
Billing        -> tenant billing records and invoice/payment projections
Notifications  -> delivery ledger and notification-specific persistence
```

## Safe reconciliation pattern

```text
legacy schema
    |
    v
add canonical representation
    |
    v
backfill + deduplicate + validate
    |
    v
switch runtime reads/writes
    |
    v
add canonical constraints
    |
    v
remove legacy representation
    |
    v
rename canonical field to final domain name
```

The Staff/Service pivot cleanup follows this pattern. Appointment identity cleanup is intentionally being handled the same way because the old `users` identities and the dedicated `customers`/`staff` identities are not interchangeable.
