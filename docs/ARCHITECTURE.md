# Velora — Architecture Guide

## 1. System Shape

Velora is a Laravel multi-tenant SaaS.

At a high level:

```text
Central Application
    |
    +-- Tenant Registry / Domains
    +-- Subscription Plans
    +-- Billing / Provider Data
    +-- Central Administration
    |
    +-- Tenant Context
            |
            +-- Settings
            +-- Users / Roles
            +-- Services
            +-- Staff / Schedules
            +-- Appointments
            +-- Queue
            +-- Customers
            +-- Reports / Exports
```

## 2. Tenancy Model

Stancl Tenancy is responsible for initializing tenant context by domain and for tenant-aware database/cache/filesystem/queue behavior.

The system uses a central database connection plus dynamically managed tenant databases.

### Rules

1. Central data must remain central.
2. Tenant data must remain tenant-scoped.
3. A tenant request must never implicitly operate on another tenant's records.
4. Jobs must preserve tenant context.
5. Uploaded tenant assets must remain tenant-scoped.

## 3. Domain Structure

Business logic is organized around application domains/services rather than keeping all logic in controllers.

Examples include:

- Booking DTOs.
- Booking services.
- Booking events.
- Booking exceptions.
- Subscription service.
- Admin controllers.
- Exports.

## 4. HTTP Layers

### Public Tenant Routes

Examples:

- Booking page.
- Public services.
- Availability.
- Staff information required by booking.
- Public queue status.

### Authenticated Tenant Routes

Examples:

- Profile.
- Customer operations.
- Billing operations.

### Admin Routes

Examples:

- Dashboard.
- Appointments.
- Staff.
- Queue.
- Customers.
- Reports.
- Settings.
- Subscription.

## 5. Authorization

Authorization uses role middleware and should be extended with resource-level authorization whenever ownership is not guaranteed by route context.

Roles currently visible in the routing layer include:

- Admin Tenant.
- Staff.
- Assistant.
- Customer.

## 6. Booking Flow

Expected flow:

```text
Public booking request
    -> validate tenant context
    -> validate service
    -> validate staff relationship
    -> validate business hours / holiday
    -> validate slot availability
    -> transactional appointment creation
    -> appointment event(s)
    -> optional queue integration / notification
```

The availability check must be repeated safely during the write operation whenever concurrency can invalidate a previously read slot.

## 7. Queue Flow

Expected state lifecycle should be explicit and deterministic:

```text
waiting -> serving -> completed
          |
          +-> waiting
```

Additional transitions such as cancellation/priority should be modeled explicitly and protected by authorization and concurrency controls.

## 8. Subscription Flow

```text
trial -> active -> grace -> expired
   |                    |
   +--------------------+

active -> cancelled
```

The exact transition rules are documented in `docs/BILLING_HARDENING.md`.

## 9. Background Processing

The system contains scheduled/command-oriented work for analytics, subscription checks, reminders and trial nudges.

Production should run:

- Queue workers.
- Scheduler.
- Failed-job monitoring.

## 10. Data Safety Rules

- Never trust a tenant/resource ID from the browser.
- Never trust a payment success redirect as proof of payment.
- Never expose central records through tenant routes.
- Do not perform expensive reporting work synchronously when it can run in a queue.
- Keep migrations backward-compatible when deploying without downtime.

## 11. Performance Principles

- Use eager loading intentionally.
- Index frequent filters and foreign keys.
- Paginate large result sets.
- Cache read-heavy analytics when safe.
- Queue exports and expensive reports.
- Avoid repeated per-day/per-row aggregate queries when an aggregated query can serve the result.

## 12. Observability

Production should make it possible to answer:

- Which tenant failed?
- Which request failed?
- Which queue job failed?
- Which payment event failed?
- Which subscription changed state and why?
- Which deployment introduced the regression?

Logs should include useful tenant/request correlation without exposing secrets.
