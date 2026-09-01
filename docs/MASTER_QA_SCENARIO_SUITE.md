# Velora — Master QA Scenario & Data Certification Suite

## Purpose

This document defines the release-level QA strategy for Velora. The goal is not only to prove that individual endpoints or pages work, but to prove that the same business event produces consistent state across tenant data, dashboards, queues, reports, notifications, subscriptions, billing and Super Admin views.

## Golden Rule

A scenario is PASS only when all applicable layers agree:

1. UI behavior is correct.
2. HTTP/API response is correct.
3. Database state is correct.
4. Related jobs/events/notifications are correct.
5. Business invariants remain true.
6. Cross-surface metrics reconcile with database truth.

## Existing Baseline

The repository already contains a substantial PHPUnit/browser suite. The QA documentation records a local baseline of hundreds of tests, but several high-value areas remain explicitly incomplete: cross-tenant isolation, negative authorization, provider webhook hardening, concurrency, storage quota, and full billing integration. This suite is an additional certification layer, not a replacement for the existing tests.

## Golden Dataset Model

Create one deterministic QA world containing multiple tenants with intentionally different data profiles.

### Tenant A — Dental Clinic

- Tenant Admin, Staff, Assistant and Customer roles.
- Multiple active/inactive services.
- Multiple bookable staff members.
- Working hours, breaks, time-off and holidays.
- Resources/rooms.
- Customers in normal, blocked and returning states.
- Appointments across pending, confirmed, completed, cancelled, no-show and rescheduled cases.
- Queue entries across waiting, serving, completed, skipped and VIP states.
- Notifications and delivery records.
- Subscription and billing records.

### Tenant B — Medical Center

Use a different catalog, different staff and different customer identifiers. This tenant exists primarily to prove isolation and platform aggregation.

### Tenant C — Salon

Use a third business profile and different time/scheduling rules to prove that global code does not accidentally assume one tenant's configuration.

## Scenario Classes

### 1. Lifecycle

Signup → provisioning → verification → handoff → onboarding → operational tenant.

### 2. Business Flow

Create the business entity through the real application flow and follow all downstream effects.

### 3. Cross-Surface Reconciliation

Compare dashboard/report/API/UI values against direct database truth.

### 4. State Machine

Walk entities through valid and invalid transitions.

### 5. Negative / Security

Try invalid input, unauthorized actions, manipulated identifiers and cross-tenant access.

### 6. Failure / Recovery

Fail dependencies and verify that the core transaction remains consistent and recoverable.

### 7. Concurrency

Run competing operations simultaneously and verify deterministic outcomes.

### 8. Time Simulation

Move the application clock through trial, expiry, reminder, read-only, lock and deletion boundaries.

## Master Feature Coverage

- Environment and installation
- Super Admin authentication and authorization
- Tenant signup
- Tenant provisioning
- Email verification
- Handoff
- Onboarding
- Tenant authentication
- Role permissions
- Cross-tenant isolation
- Settings and branding
- Localization / RTL / LTR
- Services and service categories
- Staff and staff-service relationships
- Working hours / breaks / time-off / holidays
- Resources
- Customers
- Public booking
- Availability
- Appointment lifecycle
- Cancellation / reschedule / no-show
- Queue lifecycle
- Priority queue
- Notifications
- Email delivery
- WhatsApp delivery
- Reminder jobs
- Trial / active / grace / read-only / locked lifecycle
- Upgrade / downgrade / renewal / cancellation
- Stripe checkout and webhook processing
- Moyasar checkout and webhook processing
- Payment transactions
- Invoices and history
- Reports and exports
- Dashboard analytics
- Background jobs / scheduler
- Account/profile/password/avatar flows
- Tenant deletion and cleanup
- Abuse/rate limiting
- Concurrency
- Recovery
- Data reconciliation
- Business invariants

## Cross-Surface Reconciliation Examples

### Booking

A single public booking must reconcile across:

`public booking response → appointment → customer → queue → status history → availability → notification delivery → admin dashboard → reports`.

### Queue

A queue transition must reconcile across:

`queue row → appointment status → customer-facing queue status → dashboard queue count → notifications → status history`.

### Billing

A successful provider event must reconcile across:

`provider event → webhook ledger → payment transaction → invoice/history → tenant subscription → tenant access → dashboard subscription state`.

### Super Admin Aggregation

Tenant A + Tenant B + Tenant C metrics must equal the sum of the underlying tenant-level facts wherever the product defines the metric as an aggregate.

## Business Invariants

The following rules should never be violated:

- A tenant can only access its own tenant data.
- An inactive/non-bookable service cannot be publicly booked.
- A staff member cannot be booked for a service they do not provide.
- A booking cannot occupy an unavailable slot.
- Two concurrent bookings for the same protected slot cannot both succeed.
- One appointment cannot have multiple active queue entries.
- Queue and appointment status must remain consistent according to the domain rules.
- A completed billable appointment must produce the expected invoice behavior.
- A successful payment must only change commercial state after provider verification.
- Duplicate provider events must not duplicate internal side effects.
- Locked/read-only subscription states must enforce their write restrictions.
- Notification failure must not silently undo a successful core business transaction.
- Dashboard metrics must equal their defined database-derived truth.

## Timeline Simulation

Use deterministic dates/time travel to cover:

- Trial start.
- Trial warning window.
- Trial expiration.
- Read-only window.
- Lock window.
- Deletion deadline.
- Appointment reminders.
- Recurring and scheduled operations.

## Failure Matrix

For each critical flow, inject at least one failure:

- Mail provider unavailable.
- WhatsApp provider unavailable.
- Payment provider unavailable.
- Invalid webhook signature.
- Duplicate webhook.
- Out-of-order webhook.
- Job failure and retry.
- Browser refresh during submission.
- Duplicate form submission.
- Database transaction rollback.

## Data Strategy

Test data must be deterministic, human-readable and intentionally related. Avoid random independent records that cannot be traced from one feature to another.

Every important entity should have a traceable chain, for example:

`Customer → Appointment → Service → Staff → Queue → Notification → Invoice`.

## Release Certification Gates

A release is NOT certified when any of the following fail:

- Any P0 security test.
- Any cross-tenant isolation test.
- Any payment verification test.
- Any duplicate/out-of-order webhook test.
- Any critical booking concurrency test.
- Any queue concurrency test.
- Any database migration on a clean environment.
- Any required browser smoke flow.
- Any cross-surface reconciliation check.
- Any business invariant check.

## Execution Order

1. Clean environment / migrations.
2. Golden dataset creation.
3. Tenant lifecycle.
4. Authentication and role matrix.
5. Catalog/staff/schedule.
6. Booking and availability.
7. Appointment lifecycle.
8. Queue lifecycle.
9. Notifications.
10. Subscription lifecycle.
11. Billing providers/webhooks.
12. Reports/exports/dashboard reconciliation.
13. Failure/recovery.
14. Concurrency.
15. Deletion/cleanup.
16. Final full regression and certification.

## Implementation Convention

Master scenario tests belong under:

`tests/Feature/QA/`

Recommended groups:

- `qa`
- `master-scenario`
- `reconciliation`
- `security`
- `concurrency`
- `billing`

Every scenario should contain a business-oriented name and explicit assertions for the state that matters. Avoid tests that only assert HTTP 200/201 when a stronger database or invariant assertion is possible.

## Current Repository Risks to Keep in Scope

- Committed `.env` must never be used as a production secret store.
- Moyasar webhook verification should fail closed when its secret is absent.
- Webhook event processing must distinguish received/processing/processed/failed states.
- Subscription state transitions should move out of request middleware toward dedicated lifecycle/application services.
- Legacy and new payment abstractions should eventually be consolidated.
- Generated reports/artifacts and runtime inspection files should not be versioned unnecessarily.
- CI should consistently exercise MySQL for database-specific behavior and concurrency-sensitive logic.

## Next QA Deliverables

1. Golden Dataset builder/fixture layer.
2. Master business-flow scenarios.
3. Cross-surface reconciliation helpers.
4. Role and tenant-isolation matrix.
5. Billing/webhook certification suite.
6. Concurrency suite.
7. Browser smoke flows for the highest-value journeys.
8. Release certification report generated from the test run.
