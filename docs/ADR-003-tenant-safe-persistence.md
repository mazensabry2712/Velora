# ADR-003 — Explicit Tenant-Safe Persistence Boundaries

## Status
Accepted

## Decision
Tenant-scoped persistence must be reached through a tenant-aware repository or infrastructure adapter. Central persistence must remain explicit and must never be selected implicitly from arbitrary request data.

Application Actions receive already-authorized tenant context from the infrastructure/runtime and do not trust tenant IDs supplied by browser payloads when the host or authenticated context establishes the tenant.

Tenant-aware asynchronous work must persist enough context to restore the tenant before touching tenant data.

## Consequences
- Lower risk of cross-tenant reads and writes.
- Easier isolation testing.
- Clearer review boundary for repositories and queries.
- Some legacy models/services remain temporarily as compatibility adapters during migration.
