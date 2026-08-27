# Architecture Current State

Velora is being migrated incrementally to a modular monolith. Existing legacy services remain as adapters until replacement use cases are covered by regression tests.

The current target flow is:

```text
HTTP / Webhook
    -> Request / Authorization
    -> Application Action
    -> Domain contract / rule
    -> Infrastructure adapter
    -> Central or Tenant persistence
```

No test execution is part of this architecture-only phase.
