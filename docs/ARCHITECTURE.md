# Velora — Architecture Guide

Velora is a modular monolith Laravel SaaS. The current refactor follows a pragmatic Domain-Driven Design approach without introducing microservices prematurely.

## 1. Target dependency direction

```text
Interfaces (HTTP / Console / Webhooks)
              |
              v
        Application layer
        (use cases/actions)
              |
              v
          Domain layer
   (rules, contracts, DTOs, events)
              |
              v
       Infrastructure layer
 (Eloquent, Stancl, Stripe, Moyasar,
      Mail/SMS, cache, storage)
```

Dependencies must point inward. Domain code must not depend on controllers, Blade views, HTTP requests, vendor SDKs, or concrete infrastructure implementations.

## 2. Application structure

```text
app/
├── Application/
│   ├── Booking/Actions/
│   ├── Pricing/Actions/
│   ├── Tenant/Actions/
│   └── Shared/Contracts/
│
├── Domain/
│   ├── Booking/
│   │   ├── DTOs/
│   │   ├── Events/
│   │   ├── Exceptions/
│   │   └── Services/
│   └── Shared/Contracts/
│
├── Infrastructure/
│   └── Persistence/
│
├── Interfaces/
│   └── Http/...
│
├── Payments/
│   └── Contracts/ + Gateways/
│
├── Repositories/
│   ├── Contracts/
│   └── Eloquent/
│
└── Services/
    └── legacy/integration services being migrated
```

The existing `Services`, `Repositories`, and `Payments` folders remain during migration so production behavior is not rewritten unnecessarily. New use cases should enter through `Application/*/Actions` and concrete integration work should move behind `Domain/*/Contracts` or `Infrastructure/*` boundaries.

## 3. Application Actions

Application Actions represent one business use case. Controllers should coordinate HTTP concerns only.

Examples already introduced:

- `Application\Tenant\Actions\RegisterTenant`
- `Application\Pricing\Actions\SetCountryOverride`
- `Application\Booking\Actions\CreateBooking`

The action owns orchestration; validation/serialization belongs to Interfaces and business rules belong to Domain.

## 4. Central vs Tenant boundaries

Velora has two data contexts.

### Central database

```text
Tenants / domains
Subscription plans
Country pricing / tax
Central users / administration
Billing provider state
System settings
```

### Tenant database

```text
Tenant users / roles
Customers
Services
Staff / schedules
Appointments
Queues
Tenant settings
Tenant reports / operational data
```

A request must be classified as central or tenant before persistence access is performed.

## 5. Tenancy lifecycle

```text
HTTP request
    |
    v
Central/tenant domain classification
    |
    v
Stancl tenant initialization
    |
    v
Application use case
    |
    v
Domain rules
    |
    v
Tenant-scoped infrastructure
```

Rules:

1. Never accept tenant identity from an untrusted browser field when the host/domain already establishes it.
2. Never query tenant records using the central connection by accident.
3. Never expose central records from tenant controllers.
4. Tenant-aware jobs must preserve tenant context.
5. Tenant uploads/cache keys should carry tenant scope where applicable.

## 6. Booking domain

The booking engine already contains explicit DTOs, events, exceptions, slot validation and concurrency protection.

```text
Create booking request
    -> Application\Booking\Actions\CreateBooking
    -> Domain\Booking\Services\BookingCreationService
    -> SlotEngine
    -> transactional write
    -> AppointmentCreated event
```

Concurrent booking protection must remain inside the write transaction. Availability should be revalidated while the relevant records are locked.

## 7. Payment architecture

Payment gateway selection is separated into two concepts:

### Gateway capability contract

`Domain\Shared\Contracts\PaymentGatewayResolver`

### Provider implementation

`Services\PaymentGatewayRouter`

The application/domain layer can depend on the contract while cache, settings and Eloquent remain implementation details.

Actual payment execution continues through:

```text
Application use case
      |
      v
Payment boundary
      |
      v
PaymentGatewayManager
      |
 ┌────┼─────────┐
Stripe Moyasar Paymob ...
```

Payment status must be driven by verified provider events, not browser redirects.

## 8. Repository boundary

Repositories expose persistence capabilities through contracts.

```text
Domain/Application
      |
      v
Repository contract
      |
      v
Eloquent repository
      |
      v
Central or tenant database
```

A repository should not contain business rules. It should load, persist and query data required by a use case.

## 9. Transactions

Application code should depend on `Application\Shared\Contracts\TransactionManager` instead of calling `DB::transaction()` directly when transaction orchestration itself is part of the use case boundary.

Laravel's implementation is `Infrastructure\Persistence\LaravelTransactionManager`.

## 10. Controllers

Controllers should follow this shape:

```php
public function store(StoreRequest $request)
{
    $result = $this->action->execute($request->validated());

    return response()->json(...);
}
```

Controllers should not:

- perform multi-step domain orchestration;
- contain provider-specific payment code;
- query multiple unrelated models to implement a use case;
- decide tenant identity from arbitrary request values;
- duplicate state-transition rules.

## 11. State transitions

Appointment, queue, subscription and payment lifecycles should have explicit allowed transitions. State changes must be centralized so an endpoint cannot bypass invariants.

### Appointment

```text
pending -> confirmed -> checked_in -> in_service -> completed
   |            |
   +----------> cancelled / no_show
```

### Subscription

```text
trial -> active -> grace -> expired
             |
             +-> cancelled
```

### Payment

```text
pending -> processing -> paid
    |             |
    +----------> failed / cancelled

paid -> refunded
```

The exact product rules remain authoritative in the billing hardening documentation.

## 12. Authorization

Authorization is layered:

```text
Authentication
   + tenant membership
   + role/permission
   + resource ownership/scope
   = authorization decision
```

A role alone is not sufficient when a resource can cross tenant or user boundaries.

## 13. Events and jobs

Events describe facts:

- `AppointmentCreated`
- `AppointmentStatusChanged`
- `PaymentSucceeded`
- `SubscriptionActivated`
- `TenantCreated`

Jobs perform deferred work:

- reminders
- exports
- reports
- email/SMS
- reconciliation
- cleanup

Event listeners should not silently change authorization or tenant scope.

## 14. Localization

Locale resolution is infrastructure/application behavior. Views should only consume translation keys.

Every landing/signup key used by Blade must exist in the supported locale files. Translation-key contract tests should protect against schema drift between Blade and `lang/*` files.

## 15. Migration strategy

The project should be migrated incrementally:

1. Keep existing behavior stable.
2. Introduce an Application Action for one use case.
3. Move transport-independent rules into Domain.
4. Introduce contracts only where a dependency crosses a boundary.
5. Move concrete integrations into Infrastructure.
6. Retire the old service/controller path after tests prove equivalence.

Do not rewrite the whole project in a single change.

## 16. Quality gates

Before production:

- all feature/unit tests pass;
- architecture/dependency tests pass;
- tenant isolation tests pass;
- payment webhook idempotency tests pass;
- localization key tests pass;
- PHP static analysis has no new high-severity findings;
- queue/scheduler/failed-job monitoring is configured;
- backups and restore procedures are verified.
