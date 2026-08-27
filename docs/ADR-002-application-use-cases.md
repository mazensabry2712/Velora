# ADR-002 — Application Use Cases Own Orchestration

## Status
Accepted

## Decision
All new write workflows should be represented by an Application Action. Controllers are HTTP adapters and should only validate/authorize input, invoke the Action, and serialize the result.

For complex workflows, the Action may coordinate multiple domain contracts and repositories inside a transaction. Provider-specific integrations, notifications, persistence details and framework APIs stay behind Infrastructure adapters.

## Current examples

- `CreateBooking`
- `CreateAdminAppointment`
- `AddDirectQueueEntry`
- `TransitionQueueEntry`
- `RequestSubscriptionUpgrade`
- `RegisterTenant`

## Migration rule
Existing controllers/services are migrated incrementally. A legacy implementation remains in place until the equivalent Action is covered by tests and the old path has no remaining callers.
