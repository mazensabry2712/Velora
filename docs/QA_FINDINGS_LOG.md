# Velora QA Findings Log

This log records defects discovered by the master QA program, the minimal fix, and the regression guard.

## Finding QA-BILLING-002 — Moyasar subscription activation could bypass the canonical central-connection contract

**Area:** Billing / Moyasar subscription activation

**Root cause:** `MoyasarService::activateSubscription()` used direct `DB::table()` calls for `tenant_subscriptions` and `subscription_plans`. The canonical `TenantSubscription` and `SubscriptionPlan` models explicitly bind these records to `tenancy.database.central_connection`. Direct DB calls therefore risked connection drift when the configured central connection differs from the default connection, especially while tenant context is initialized.

**Fix implemented:** The service now uses the canonical `TenantSubscription` and `SubscriptionPlan` models for lookup and update. This preserves the existing business behavior while enforcing the same central connection contract as the rest of the billing layer. It also fails explicitly when the tenant subscription record does not exist instead of silently updating zero rows.

**Regression:** `MoyasarCentralConnectionScenarioTest` activates a subscription while tenant context is active and verifies that the canonical central subscription record is updated with active status, Moyasar payment method, amount, plan and lifecycle timestamps.

---

## Finding QA-AUTH-001 — Tenant Staff/Assistant could mutate administrative configuration

**Area:** Tenant admin authorization / services / staff / settings

**Root cause:** The tenant admin route group admitted `Admin Tenant|Staff|Assistant`, while affected controllers had no method-level authorization and FormRequests returned `authorize() = true`. This allowed Staff/Assistant to reach service, schedule, staff and settings mutations.

**Fix implemented:** Added minimal role guards. `Admin Tenant` is required for service/schedule mutations, tenant settings writes, and staff create/update/delete. Read methods remain unchanged.

**Regression:** `AdminAuthorizationMatrixScenarioTest` and `AuthorizationMatrixExpandedScenarioTest` verify Staff/Assistant rejection and Tenant Admin success.

---

## Current Expanded Authorization Coverage

`AuthorizationMatrixExpandedScenarioTest` adds regression coverage for:

- Staff cannot create/delete staff accounts.
- Assistant cannot create time slots.
- Assistant cannot change working-day configuration.
- Tenant Admin can create a time slot and create a staff account.

This extends the authorization gate without changing Queue/Appointment permissions whose Staff/Assistant behavior may be intentional and requires separate business-policy evidence.

---

## Test Infrastructure Policy

Every production defect discovered by Master QA must produce a regression test before the next feature family is accepted.

The canonical certification environment is MySQL. SQLite may be used for fast unit checks but is not sufficient evidence for tenant, locking, webhook, billing, or certification gates.

## Package / Engineering Policy

Do not add a package merely to solve a problem that existing Laravel/PHP/project code can solve correctly. A package is justified only when it materially reduces complexity or risk for a real requirement. Such decisions must be documented.

## Current Certification Rule

A feature family is not complete until:

1. Happy path passes.
2. Negative and edge cases pass.
3. Data invariants pass.
4. Dependent projections reconcile.
5. Regression tests pass in MySQL CI.
6. Security/authorization and concurrency gates pass where applicable.
7. Known production defects are fixed with regression coverage or explicitly block release.

## Current Handoff State

The verified main line contains the Master QA foundation, booking/appointment/queue/customer/notification coverage, Moyasar webhook hardening, tenant isolation test infrastructure, reporting/deletion safeguards, and authorization hardening. Fresh MySQL CI evidence is still required for commits added after the last completed Master QA run.

Completed passing evidence from the earlier Master QA run includes:

- Environment foundation
- Public booking golden flow
- Booking rules/negative cases
- Appointment lifecycle
- Queue lifecycle and business-date correctness
- Call-next locking/date scoping
- Customer/dashboard reconciliation
- Queue notification lifecycle and recovery basics
- Moyasar webhook security and payment-verification scenarios

Added/fixed afterward and awaiting fresh MySQL CI evidence:

- Tenant token isolation
- Tenant resource isolation
- Tenant test transaction connection safety
- Super Admin tenant/subscription reconciliation
- Reporting customer reconciliation
- Tenant deletion safety
- Expanded tenant authorization matrix
- Moyasar canonical central-connection activation

Next priority:

```text
Fresh MySQL CI on current main
→ Billing/Webhooks reconciliation
→ Subscription access reconciliation
→ Full tenant/resource authorization matrix
→ Super Admin aggregation and revenue reconciliation
→ Reporting/export reconciliation
→ Deletion/cleanup safety
→ Browser smoke / final certification
```

Do not mark Velora production-certified merely because the existing global PHPUnit suite is green. Certification requires the master scenario suite, reconciliation, security, concurrency and billing gates above.
