# Velora — Project Status

> **Snapshot:** Main branch reviewed on 2026-08-29.
>
> This document records what is already implemented in the repository and what still needs to be completed before calling Velora fully production-ready.

## 1. Product Definition

Velora is a multi-tenant appointment-booking SaaS for businesses that need:

- Public online booking.
- Services and staff management.
- Staff schedules and availability.
- Appointment management.
- Queue / waiting-room management.
- Customer management.
- Reports and exports.
- Admin operations.
- Tenant-level settings and localization.
- Subscription plans and usage limits.
- Stripe / Moyasar billing flows.
- Trial, grace-period and expiry lifecycle.

## 2. Current Architecture

The project is built around:

- Laravel 12.
- PHP 8.2+ with dependency resolution pinned to PHP 8.4.
- Blade + Vite + Tailwind CSS 4.
- Laravel Sanctum.
- Spatie Laravel Permission.
- Stancl Tenancy v3.
- Stripe PHP SDK.
- Moyasar integration.
- Laravel Excel exports.
- DomPDF and QR-code support.
- MySQL / tenant databases.
- Queued and scheduled background operations.

## 3. Implemented Areas

### Core SaaS

- [x] Multi-tenant domain initialization.
- [x] Tenant-specific database support.
- [x] Tenant cache/filesystem/queue bootstrapping.
- [x] Tenant locale switching.
- [x] Tenant-level settings.
- [x] Admin role separation.
- [x] Onboarding flow.

### Authentication / Signup

- [x] Central signup creates Tenant + Domain.
- [x] Signup verification email flow.
- [x] Verification token hashing/encryption and expiry.
- [x] Tenant-language aware verification page.
- [x] Arabic RTL verification rendering.
- [x] First Tenant Admin creation gated by email verification.
- [x] Unverified Tenant Admin cannot log in.
- [x] Tenant handoff requires verified email + existing verified user + ready workspace.
- [x] Provisioning/handoff regression tests.

### Booking

- [x] Public booking page.
- [x] Services API.
- [x] Staff API.
- [x] Staff-service assignment.
- [x] Working days.
- [x] Time slots.
- [x] Slot availability checks.
- [x] Booking creation service.
- [x] Appointment status operations.
- [x] Booking-related domain events.
- [x] Queue integration.

### Queue

- [x] Public queue status.
- [x] Admin queue operations.
- [x] Call-next flow.
- [x] Serve / complete flow.
- [x] Return-to-waiting flow.
- [x] Move-to-next-day flow.
- [x] Priority handling.
- [x] Print / export support.
- [x] Queue lifecycle notification event generation for position changes, almost-turn transitions and turn-now transitions.
- [x] Queue lifecycle notification delivery through the shared NotificationDelivery contract.
- [ ] Final local validation of the new queue lifecycle test suite.

### Appointment Notifications

- [x] Notification delivery ledger with event/channel/dedupe identity.
- [x] Email reminder delivery is queue-backed and isolated from reminder discovery.
- [x] `appointment.reminder_24h` flow.
- [x] `appointment.reminder_1h` flow.
- [x] Reminder deduplication by event + channel + public appointment reference.
- [x] Delivery attempt/status tracking and retry/final-failure handling.
- [x] ReminderLog synchronization with NotificationDelivery.
- [x] Tenant-aware reminder processing without breaking tenant test transaction context.
- [x] Scheduler remains the existing `reminders:process` entry point.
- [x] Supported notification locales satisfy English notification key and placeholder parity.
- [x] Queue lifecycle notifications use the same notification delivery foundation and locale catalog.

### Admin

- [x] Dashboard.
- [x] Appointments management.
- [x] Staff management.
- [x] Customers management.
- [x] Settings.
- [x] Reports.
- [x] Assistants management.
- [x] Subscription management.
- [x] Billing operations.
- [x] Profile management.

### Subscription / Billing

- [x] Subscription plans.
- [x] Active / trial / grace / expired / cancelled lifecycle.
- [x] User usage limits.
- [x] Appointment usage limits.
- [x] Trial extension flow.
- [x] Stripe customer/price fields.
- [x] Stripe checkout / portal routes.
- [x] Moyasar callback flow.
- [x] Subscription history display.
- [x] Founder trial alerts.

### Testing

- [x] Feature test structure.
- [x] Admin tests.
- [x] Billing tests.
- [x] Public booking tests.
- [x] Public queue tests.
- [x] Appointment tests.
- [x] Appointment/queue integration tests.
- [x] Locale tests.
- [x] Multi-region / tenancy-oriented tests.
- [x] Super-admin test structure.
- [x] Signup / verification / tenant-handoff regression tests.
- [x] Appointment reminder delivery tests.
- [x] Notification locale key/placeholder parity tests.
- [x] Queue lifecycle notification regression test suite added.
- [x] Last confirmed local full suite before Queue Lifecycle implementation: **570 tests, 5624 assertions, 0 failures, 0 errors**.
- [ ] Queue lifecycle implementation requires final local test validation after pull.

## 4. Known Incomplete / Risk Areas

These are not cosmetic tasks. They affect production readiness.

### Critical

- [ ] Audit every explicit `DB::connection('mysql')` call and confirm central-vs-tenant intent.
- [ ] Complete tenant isolation audit for every endpoint accepting a resource ID.
- [ ] Verify billing webhooks are the source of truth for payment state.
- [ ] Verify webhook idempotency and duplicate-event handling.

### High Priority

- [ ] Implement real storage usage tracking for subscription limits.
- [ ] Decide whether invoice history should become a real invoice/accounting model.
- [ ] Add/verify rate limiting for login, public booking, queue polling and billing endpoints.
- [ ] Move subscription state transitions out of ordinary request middleware where practical.
- [ ] Review authorization at object/resource level, not only by role.
- [ ] Review database indexes for high-volume tenant queries.

### Medium Priority

- [ ] Split heavy dashboard query logic into dedicated services/query objects.
- [ ] Finish localization cleanup and remove hard-coded user-facing strings from business logic.
- [ ] Replace placeholder/dead artifacts such as empty commands when confirmed unused.
- [ ] Improve production documentation and deployment runbook.
- [ ] Complete browser/mobile UI QA.

## 5. Current Readiness

Velora has a strong core SaaS implementation, but this repository should currently be treated as **production-candidate**, not as a system that has already passed a complete security, billing and deployment certification.

The latest confirmed local automated regression baseline before Queue Lifecycle implementation is green at **570 tests / 5624 assertions**.

The Queue Lifecycle implementation is now present in the repository, with final local verification pending.

## 6. Definition of Success

Velora is considered production-ready only when all P0 and P1 items in:

- `docs/PRODUCTION_ROADMAP.md`
- `docs/SECURITY_TENANCY_AUDIT.md`
- `docs/BILLING_HARDENING.md`
- `docs/TESTING_QA_PLAN.md`
- `docs/PRODUCTION_CHECKLIST.md`

are complete and verified.
