# ADR-004 — Events for Non-Critical Side Effects

## Status
Accepted

## Decision
A successful domain/application write must not depend on non-critical side effects such as email, SMS, analytics or notifications. The primary transaction commits first; a fact event is emitted and listeners/jobs perform secondary work asynchronously where appropriate.

Critical payment verification and persistence remain synchronous and transactional when required by the provider contract.

## Consequences
- User-facing workflows are more resilient to mail/SMS/provider outages.
- Failed secondary work can be retried independently.
- Event payloads must contain enough immutable context for safe processing.
- Tenant context must be restored before any tenant-scoped listener touches tenant data.
