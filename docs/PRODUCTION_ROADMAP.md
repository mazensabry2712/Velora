# Velora — Production Roadmap

## Goal

Turn the current Velora SaaS implementation into a commercially dependable, secure, observable and maintainable production platform.

## Priority Model

- **P0:** Can cause data loss, cross-tenant access, incorrect payment state, or a major production outage.
- **P1:** Can materially weaken security, billing, reliability or scalability.
- **P2:** Quality, maintainability, UX and operational improvements.
- **P3:** Nice-to-have product growth work after core hardening.

---

## Phase 0 — Freeze and Baseline

### P0

- [ ] Confirm `main` is the only production source of truth.
- [ ] Record current PHP, Laravel, Node, MySQL and Redis versions.
- [ ] Run a clean dependency install from lock files.
- [ ] Run the complete test suite from a clean environment.
- [ ] Run `php artisan route:list` and store the generated route inventory.
- [ ] Run static analysis / linting / formatting checks where configured.
- [ ] Confirm no secrets, real `.env` values or credentials exist in git.
- [ ] Tag the current baseline before hardening.

---

## Phase 1 — Tenant Isolation (P0)

This is the most important engineering phase for a multi-tenant SaaS.

- [ ] Inventory all central tables.
- [ ] Inventory all tenant tables.
- [ ] Inventory every use of `DB::connection(...)`.
- [ ] Classify each query as central or tenant-scoped.
- [ ] Remove accidental hard-coded connection names where the tenancy abstraction should be used.
- [ ] Audit every controller/service/repository that accepts an ID.
- [ ] Ensure a tenant cannot address another tenant's appointment, customer, staff, service, queue entry, invoice or setting by guessing an ID.
- [ ] Add cross-tenant negative tests for every critical resource.
- [ ] Add tenant-context assertions to sensitive service methods.
- [ ] Verify tenant filesystem prefixes.
- [ ] Verify tenant cache prefixes/tags.
- [ ] Verify tenant queues and queued jobs keep tenant context.
- [ ] Verify central domains cannot access tenant-only routes.
- [ ] Verify tenant domains cannot access central-only operations.

### Completion rule

A tenant-isolation regression test must fail whenever a tenant can read or mutate another tenant's resource.

---

## Phase 2 — Authentication and Authorization (P0/P1)

- [ ] Audit tenant-aware login.
- [ ] Verify login throttling.
- [ ] Verify session regeneration after authentication.
- [ ] Verify logout/session invalidation.
- [ ] Verify password reset.
- [ ] Verify email verification where required by the product.
- [ ] Verify account deletion behavior.
- [ ] Verify role assignment cannot be escalated by a normal tenant user.
- [ ] Verify Admin Tenant vs Staff vs Assistant permissions.
- [ ] Add policy/resource authorization where route role checks are not sufficient.
- [ ] Add IDOR regression tests for all `{id}` operations.
- [ ] Verify profile/avatar upload authorization and validation.

---

## Phase 3 — Booking and Scheduling Hardening (P1)

- [ ] Prevent double booking under concurrency.
- [ ] Use database transactions around appointment creation where required.
- [ ] Validate slot availability again inside the write transaction.
- [ ] Add concurrency tests for the same slot.
- [ ] Verify timezone handling per tenant.
- [ ] Verify DST behavior for supported regions.
- [ ] Verify staff schedule overrides and holidays.
- [ ] Verify cancellation and rescheduling rules.
- [ ] Verify queue/appointment state transitions cannot become invalid.
- [ ] Verify booking spam controls.
- [ ] Verify public endpoints do not expose sensitive tenant data.

---

## Phase 4 — Queue Reliability (P1)

- [ ] Formalize queue state machine.
- [ ] Add transition validation for every queue status.
- [ ] Add concurrency protection around `call-next`.
- [ ] Verify priority/VIP ordering under concurrent requests.
- [ ] Verify duplicate queue insertion prevention where required.
- [ ] Verify public queue polling rate limits.
- [ ] Verify historical queue retention rules.
- [ ] Add failure/retry tests for queue operations.

---

## Phase 5 — Billing and Subscription Hardening (P0/P1)

See `docs/BILLING_HARDENING.md` for the detailed plan.

Core requirements:

- [ ] Webhook-first payment state.
- [ ] Signature verification.
- [ ] Idempotent webhook processing.
- [ ] Duplicate-event safety.
- [ ] Subscription renewal handling.
- [ ] Failed-payment handling.
- [ ] Cancellation handling.
- [ ] Upgrade/downgrade behavior.
- [ ] Trial and grace lifecycle correctness.
- [ ] Invoice/transaction reconciliation.
- [ ] Storage usage implementation.
- [ ] Billing audit trail.

---

## Phase 6 — Performance and Scalability (P1/P2)

- [ ] Review all dashboard queries.
- [ ] Replace repetitive reporting queries with aggregated datasets where justified.
- [ ] Review N+1 queries.
- [ ] Add indexes based on actual query patterns.
- [ ] Add pagination to large admin lists.
- [ ] Queue exports and expensive reports.
- [ ] Cache safe read-heavy analytics.
- [ ] Verify Redis is used correctly for queues/cache in production.
- [ ] Define queue worker scaling rules.
- [ ] Define database backup/restore strategy.
- [ ] Load test booking and queue endpoints.

---

## Phase 7 — Security Hardening (P0/P1)

See `docs/SECURITY_TENANCY_AUDIT.md`.

- [ ] Rate limiting.
- [ ] CSRF verification for state-changing web routes.
- [ ] Strict validation for uploads.
- [ ] File type and size restrictions.
- [ ] Secure image handling.
- [ ] Secure headers.
- [ ] Production error handling.
- [ ] No stack traces to users.
- [ ] Secret management.
- [ ] Least-privilege database credentials.
- [ ] HTTPS-only production configuration.
- [ ] Secure cookies.
- [ ] Audit logging for privileged actions.

---

## Phase 8 — Localization and UX (P2)

- [ ] Complete Arabic/RTL review.
- [ ] Move remaining hard-coded strings to localization files.
- [ ] Verify all date/time/currency formatting is locale-aware.
- [ ] Verify validation messages in supported languages.
- [ ] Verify tenant branding and logos.
- [ ] Test admin panel on mobile/tablet/desktop.
- [ ] Test public booking on mobile.
- [ ] Test accessibility basics.

---

## Phase 9 — Observability and Operations (P1)

- [ ] Centralized application logs.
- [ ] Queue failure monitoring.
- [ ] Scheduled-task monitoring.
- [ ] Database health checks.
- [ ] Payment webhook monitoring.
- [ ] Error tracking.
- [ ] Basic uptime monitoring.
- [ ] Alerting for repeated subscription/billing failures.
- [ ] Alerting for failed queues.
- [ ] Document incident response.

---

## Phase 10 — Release Engineering (P1)

- [ ] CI pipeline for install + lint + tests + build.
- [ ] Production deployment script/runbook.
- [ ] Safe migrations process.
- [ ] Queue worker restart strategy.
- [ ] Scheduler setup verification.
- [ ] Storage linking verification.
- [ ] Cache/config/route optimization in deployment.
- [ ] Rollback procedure.
- [ ] Backup verification.
- [ ] Restore drill.

---

## Phase 11 — Product Completion (P2/P3)

After hardening:

- [ ] Better onboarding analytics.
- [ ] Subscription conversion analytics.
- [ ] Customer self-service billing improvements.
- [ ] Advanced reports.
- [ ] Notification preferences.
- [ ] SMS/WhatsApp integrations if commercially required.
- [ ] Calendar integrations if required.
- [ ] API documentation for public/integration endpoints.
- [ ] Optional mobile apps.

---

## Definition of Done

Velora can be called production-ready only when:

1. P0 issues are closed.
2. P1 security/billing/tenancy items are verified by automated tests.
3. Full tests pass from a clean checkout.
4. Production build succeeds.
5. Backups are verified and restorable.
6. Payment webhook flows are tested end-to-end.
7. Cross-tenant negative tests pass.
8. Monitoring and alerting are active.
9. Rollback is documented and tested.
10. `docs/PRODUCTION_CHECKLIST.md` is fully checked.
