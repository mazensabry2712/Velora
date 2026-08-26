# Velora — Security & Multi-Tenancy Audit

## Purpose

This document is the security contract for a multi-tenant Velora installation. The main threat to control is accidental or malicious cross-tenant access.

## P0 — Tenant Boundary

- [ ] Identify every central database table.
- [ ] Identify every tenant database table.
- [ ] Document the reason for every central database query executed while tenant context is active.
- [ ] Search the codebase for all `DB::connection('mysql')` usages.
- [ ] Replace ambiguous hard-coded connections with named abstractions where appropriate.
- [ ] Verify `tenant()` is available before tenant-only operations.
- [ ] Verify central routes cannot initialize a tenant accidentally.
- [ ] Verify tenant routes cannot reach central administration endpoints.
- [ ] Verify tenant domain identification cannot be spoofed via untrusted headers.

## P0 — IDOR / Resource Ownership

Every endpoint using an identifier must prove that the target resource belongs to the current tenant.

Audit at minimum:

- [ ] Appointment IDs.
- [ ] Customer/user IDs.
- [ ] Staff IDs.
- [ ] Service IDs.
- [ ] Time slot IDs.
- [ ] Working-day IDs.
- [ ] Queue IDs.
- [ ] Invoice/payment IDs.
- [ ] Settings IDs.
- [ ] Export/report parameters.

### Required test pattern

For each protected resource:

1. Create tenant A and tenant B.
2. Create the resource in tenant B.
3. Authenticate as a valid user in tenant A.
4. Try to read the B resource.
5. Try to update the B resource.
6. Try to delete the B resource.
7. Expect denial and verify the B resource is unchanged.

## P0 — Authorization

- [ ] Admin Tenant permissions reviewed.
- [ ] Staff permissions reviewed.
- [ ] Assistant permissions reviewed.
- [ ] Customer permissions reviewed.
- [ ] Super-admin / central-admin permissions reviewed.
- [ ] Role assignment restricted.
- [ ] Permission assignment restricted.
- [ ] Privileged operations require explicit authorization.
- [ ] Policies are used where route-level role middleware does not provide sufficient object-level protection.

## P1 — Authentication

- [ ] Login rate limiting.
- [ ] Session regeneration on login.
- [ ] Logout invalidates session.
- [ ] CSRF protection on web state-changing operations.
- [ ] Password reset verification.
- [ ] Password confirmation for critical actions when appropriate.
- [ ] Email verification where required.
- [ ] Account deletion is authorization-protected.
- [ ] Sanctum tokens are scoped and revoked appropriately.

## P1 — Public Booking Protection

Public endpoints are intentionally unauthenticated, so they require stronger abuse controls.

- [ ] Rate-limit service discovery.
- [ ] Rate-limit availability polling.
- [ ] Rate-limit booking attempts.
- [ ] Prevent automated slot enumeration where sensitive.
- [ ] Validate all booking input server-side.
- [ ] Validate service/staff relationship server-side.
- [ ] Re-check slot availability during the write transaction.
- [ ] Add anti-spam controls appropriate to the business.

## P1 — File Uploads

- [ ] Validate MIME type and extension.
- [ ] Validate maximum file size.
- [ ] Store uploads outside executable paths where practical.
- [ ] Generate safe server-side filenames.
- [ ] Prevent path traversal.
- [ ] Verify tenant-specific storage isolation.
- [ ] Verify public URLs cannot expose unrelated tenant files.

## P1 — Web Security

- [ ] HTTPS enforced in production.
- [ ] Secure cookies.
- [ ] SameSite configuration reviewed.
- [ ] HSTS configured where appropriate.
- [ ] Content-Security-Policy reviewed.
- [ ] X-Content-Type-Options enabled.
- [ ] Referrer-Policy reviewed.
- [ ] Frame-ancestors / clickjacking protection reviewed.
- [ ] Production debug mode disabled.

## P1 — Secrets

- [ ] `.env` is never committed.
- [ ] API keys live in environment/secret management.
- [ ] Stripe secrets are not logged.
- [ ] Moyasar secrets are not logged.
- [ ] SMTP credentials are not logged.
- [ ] Database credentials follow least privilege.

## P1 — Logging / Audit

Privileged actions should be auditable without recording secrets.

- [ ] Login failures recorded safely.
- [ ] Role/permission changes recorded.
- [ ] Subscription changes recorded.
- [ ] Payment state changes recorded.
- [ ] Appointment destructive actions recorded where needed.
- [ ] Settings changes recorded where business-critical.
- [ ] Audit logs include tenant ID where tenant context exists.
- [ ] Sensitive fields are masked.

## P1 — Queue and Job Security

- [ ] Queued jobs preserve tenant context.
- [ ] Tenant ID is available explicitly where needed.
- [ ] Jobs do not accidentally execute against central data.
- [ ] Failed jobs are monitored.
- [ ] Retry behavior is idempotent.
- [ ] Destructive jobs have safe retry semantics.

## P2 — Dependency and Supply Chain

- [ ] Run Composer audit/security checks.
- [ ] Run npm dependency audit appropriate to the release process.
- [ ] Keep lock files committed.
- [ ] Review abandoned packages.
- [ ] Review package update cadence.
- [ ] Pin production images/toolchains where required.

## Security Exit Criteria

Security sign-off requires:

- Zero known P0 tenant-isolation findings.
- Zero known P0 authorization findings.
- Billing webhooks verified.
- Public booking abuse controls verified.
- Cross-tenant negative tests passing.
- Production debug disabled.
- Secrets verified absent from git history/current tree.
