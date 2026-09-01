# Velora Tenant Isolation Gate

## Purpose

This gate verifies that authenticated tenant API access is bound to the initialized tenant and cannot cross tenant boundaries by changing the tenant context.

## Current Security Contract

1. `InitializeTenancyByToken` selects the tenant from `X-Tenant-ID` or `tenant_id`.
2. `auth:sanctum` authenticates the bearer token inside the initialized tenant context.
3. `EnsureTokenBelongsToTenant` requires the authenticated token to have the ability `tenant:{current-tenant-id}`.
4. A token scoped to Tenant A must not pass authorization after Tenant B is initialized.

## Regression Coverage

`tests/Feature/QA/TenantIsolationSecurityScenarioTest.php`

- Token scoped to the current tenant is accepted.
- Tenant A token is rejected with HTTP 403 when Tenant B is initialized.

## Required Resource-Level Gate

The token gate is necessary but not sufficient. Every resource endpoint that accepts a resource identifier must also be verified for ownership within the current tenant database.

Minimum resources for the next gate:

- Appointments
- Customers
- Queues
- Invoices
- Notifications
- Reports
- Settings

For each resource, test both read and write paths using an identifier created in another tenant.

## Certification Rule

Tenant isolation is not PASS until both layers pass:

- Authentication/token tenant binding.
- Resource-level tenant ownership / current-tenant database isolation.

No package is required for this gate; existing Sanctum + Stancl Tenancy middleware is the canonical implementation.
