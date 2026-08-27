# ADR-001 — Modular Monolith as the Application Architecture

## Status
Accepted

## Context
Velora is a Laravel SaaS with central and tenant data contexts, booking/queue operations, subscriptions, billing providers, administration, reporting and localization. The project already contains stable legacy services and repositories, so a full rewrite would create unnecessary regression risk.

## Decision
Use a modular monolith with four dependency layers:

```text
Interfaces -> Application -> Domain <- Infrastructure
```

- Interfaces owns HTTP, validation, serialization and webhooks.
- Application owns use cases and orchestration.
- Domain owns business contracts, rules, value objects and domain events.
- Infrastructure owns Eloquent, Stancl Tenancy, payment SDKs, mail/SMS, cache and storage.

Central and tenant persistence remain explicit boundaries. Cross-boundary dependencies must use contracts/adapters.

## Consequences
Positive:
- Clear business-use-case boundaries.
- Easier unit and feature testing.
- Safer migration from existing services.
- Payment and persistence implementations can change without changing core use cases.
- The system can later extract a bounded module into a service if scale requires it.

Trade-offs:
- More classes and interfaces.
- A temporary compatibility layer is required while legacy services are retired.
- Architecture checks become part of the development workflow.

## Migration rule
Do not move or delete legacy code until equivalent behavior is protected by tests. New code should enter through Application Actions and should not add new business logic to controllers.
