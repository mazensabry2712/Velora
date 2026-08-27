# Architecture Conventions

## Naming

- Application use cases are verbs: `CreateBooking`, `TransitionQueueEntry`, `RequestSubscriptionUpgrade`.
- DTOs end with `Data`.
- Domain ports use capability-oriented names such as `SubscriptionReader` and `AppointmentCommand`.
- Infrastructure implementations expose their concrete technology in the name or namespace.

## Dependency rules

- Controllers may depend on Application Actions and input Requests.
- Application Actions may depend on Domain contracts, DTOs and approved framework abstractions.
- Domain contracts must not depend on vendor integrations or HTTP concerns.
- Infrastructure may depend on framework models and external SDKs.
- Legacy services are compatibility adapters and are not the preferred dependency for new application code.

## Write boundary

Complex writes must be atomic when their consistency requires multiple persistence operations. Use `TransactionManager` at the Application boundary.

## Side effects

Mail, SMS, analytics and other non-critical effects should be event-driven or queued after the primary write.
