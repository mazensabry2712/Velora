# Velora Master QA — Run #120 Postmortem

## Run identity

```text
Repository: mazensabry2712/Velora
Branch tested: main
Commit tested: 281268faf99337b2c9c62f3c9e679222268f76ee
Workflow: Velora Master QA
Run: #120
Database: MySQL 8.4
PHP: 8.4
Duration: 57.33s
Result: 53 passed, 6 failed
Assertions: 240
```

## Certification meaning

Run #120 is **not a certification pass**. It is a diagnostic checkpoint that exposed six failures. The failure set contains one confirmed production authorization defect, several test-fixture/test-infrastructure defects, and one deletion-path behavior that required a production-side central-connection hardening.

No later commit is considered certified until a fresh MySQL Master QA run matches that later `main` SHA and passes the relevant gates.

## Failure 1 — Onboarding authorization

### Test

`AuthorizationMatrixExpandedScenarioTest::staff_and_assistant_cannot_mutate_onboarding`

### Observed

```text
Expected: 403
Actual:   200
```

### Root cause

`OnboardingController` accepted onboarding mutations without a method-level role guard. The tenant admin route group allows `Admin Tenant|Staff|Assistant`, so Staff and Assistant could reach the following state-changing operations:

```text
saveStep1
saveStep2
saveStep3
complete
```

These operations modify tenant settings, staff configuration, service configuration, working hours, booking enablement and queue enablement.

### Classification

**Confirmed production authorization defect.**

### Remediation

Added a local `ensureTenantAdmin()` guard and applied it to all onboarding mutation methods. The read-only `index` action was intentionally left unchanged.

### Regression

The existing matrix test remains the regression guard and asserts Staff and Assistant receive `403` while the tenant admin can perform the corresponding setup operations.

## Failure 2 — Moyasar central-connection test fixture

### Test

`MoyasarCentralConnectionScenarioTest::activation_uses_the_central_subscription_connection_inside_tenant_context`

### Observed

```text
ModelNotFoundException:
No query results for model [App\\Models\\SubscriptionPlan].
```

### Root cause

The clean MySQL Master QA workflow runs migrations but does not run product seeders. The test assumed a `SubscriptionPlan` already existed.

### Classification

**Test fixture defect, not a production business failure.**

### Remediation

The test now creates a minimal valid `SubscriptionPlan` inside the central test transaction. The fixture uses all required schema fields, including `slug`, `billing_cycle`, limits and `features`.

### Regression intent

The scenario still proves the real requirement: while tenant context is active, `MoyasarService::activateSubscription()` must update the central subscription record rather than a tenant/default connection.

## Failure 3 — Tenant deletion success assertion

### Test

`TenantDeletionSafetyScenarioTest::successful_resource_cleanup_removes_tenant_subscription_and_tenant_record`

### Observed

After `subscriptions:purge-expired --force`, the test still found a `tenant_subscriptions` row for the test tenant.

### Root cause analysis

The command already resolved central subscription rows through the configured central connection, but tenant lookup used the `Tenant` model without explicitly binding the model query to that same central connection. A command running in an unexpected connection context could therefore resolve the wrong tenant state.

### Classification

**Production hardening gap in a central/tenant boundary.**

### Remediation

`PermanentlyDeleteExpiredTenants` now resolves the tenant explicitly with:

```text
Tenant::on($centralConnection)
```

before looking up the tenant and deleting its central records.

### Existing safety contract

The command intentionally deletes resources in this order:

```text
Tenant storage cleanup
        ↓
Tenant database cleanup
        ↓
Central subscription deletion
        ↓
Domain deletion
        ↓
Tenant force deletion
```

If storage/database cleanup throws, central records remain so the operation is retryable.

## Failure 4 — Tenant resource isolation teardown

### Test family

`TenantIsolationResourceScenarioTest`

### Observed

```text
Database connection [tenant] not configured.
```

### Root cause

The tenant test base stored only the dynamic connection name. Some isolation tests intentionally call `tenancy()->end()` before PHPUnit teardown. Stancl Tenancy removes the dynamic connection configuration at that point, so the test base attempted to reopen a connection named `tenant` that no longer existed.

### Secondary effect

This same root cause could leave the central fixture transaction open and make later tests try to insert an already-existing class fixture tenant, producing duplicate-primary-key failures.

### Classification

**Test infrastructure defect.**

### Remediation

`TenantTestCase` now captures the concrete `Illuminate\\Database\\Connection` objects for both the central and tenant transactions. Teardown rolls back using those captured objects before ending tenancy. It no longer depends on reopening a dynamic `tenant` connection after configuration has been removed.

## Failure 5 — Tenant token isolation teardown / fixture leakage

### Test family

`TenantIsolationSecurityScenarioTest`

### Observed

The cross-tenant test itself reached the middleware path, but teardown failed with the same dynamic-connection error. Because the first test could not complete central rollback cleanly, a later test could also encounter:

```text
Duplicate entry 'test-tenant-...' for key 'tenants.PRIMARY'
```

### Root cause

Same `TenantTestCase` dynamic-connection lifecycle problem described above.

### Classification

**Test infrastructure defect.**

### Remediation

Same concrete-connection rollback fix in `TenantTestCase`.

### Business security contract being protected

```text
Tenant A token + Tenant A
    => allowed

Tenant A token + Tenant B
    => 403 Tenant mismatch
```

The actual middleware enforces `tenant:{tenant-id}` as an explicit Sanctum ability.

## Failure 6 — Shared tenant fixture duplication

### Observed

A test attempted to recreate the class-level fixture tenant ID while a previous test still had that tenant row present.

### Root cause

This was a consequence of the same failed central rollback caused by the dynamic connection teardown issue, not an independent tenant model bug.

### Classification

**Secondary test infrastructure leakage.**

### Remediation

Fixed indirectly and intentionally by the concrete `Connection` transaction handling in `TenantTestCase`.

## Why these failures were not hidden

The correct response was not to loosen assertions, skip isolation tests, or make the deletion assertion weaker. Each failure was traced to the layer responsible for the behavior:

```text
Production authorization gap
    → production fix + regression

Missing test fixture
    → self-contained fixture

Central deletion lookup boundary
    → explicit central connection

Dynamic tenant connection teardown
    → test infrastructure fix
```

## Current remediation commits after Run #120

```text
TenantTestCase transaction safety
→ 464a5d76d7ad19743c0af244b4ab378204905c3d

Onboarding admin-only production guard
→ 117e57ede6c991c3a1806006f33b3698e7344db5

Moyasar central fixture hardening
→ c30656d91ca5f70c61f060d1bbc81c86e967981e

Tenant purge explicit central-connection tenant lookup
→ c4e397232ac439bdd6caae8ea5832621e2486248
```

## Next evidence requirement

A fresh MySQL Master QA run must be executed on the current `main` head after these fixes.

Do not use Run #120 as evidence that the current head passes. Run #120 is historical evidence for the commit it tested.

## Next QA sequence after a clean rerun

```text
1. Confirm clean Master QA on current main
2. Confirm full PHPUnit suite on current main
3. Re-run billing/subscription reconciliation
4. Expand resource-level authorization only where policy is proven
5. Run tenant isolation matrix
6. Reconcile Super Admin financial metrics
7. Reconcile Reports / Excel exports
8. Validate deletion/storage/database cleanup
9. Run deterministic Playwright browser journeys
10. Run final regression and production go/no-go
```

## Operational rule

Every new session must start by reading:

```text
README.md
docs/MASTER_QA_EXECUTION_RUNBOOK.md
docs/QA_CURRENT_HANDOFF.md
docs/QA_FINDINGS_LOG.md
docs/QA_RUN_120_POSTMORTEM.md
```

Then verify the real `refs/heads/main` SHA and match CI evidence to that SHA before modifying or certifying anything.
