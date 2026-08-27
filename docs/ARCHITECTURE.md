# Velora — Architecture Guide

Velora is a modular monolith Laravel SaaS. The refactor follows pragmatic Domain-Driven Design and explicit application boundaries without introducing microservices prematurely.

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

## 2. Bounded modules

```text
app/
├── Application/
│   ├── Booking/Actions + DTOs
│   ├── Pricing/Actions
│   ├── Queue/Actions + DTOs
│   ├── Reporting/Actions
│   ├── Subscription/Actions + Events
│   ├── Tenant/Actions
│   └── Shared/Contracts
│
├── Domain/
│   ├── Booking/
│   │   ├── DTOs/
│   │   ├── Events/
│   │   ├── Exceptions/
│   │   └── Services/
│   ├── Pricing/Contracts/
│   ├── Queue/Contracts/
│   ├── Reporting/Contracts/
│   ├── Subscription/Contracts/
│   ├── Tenant/Contracts/
│   └── Shared/Contracts/
│
├── Infrastructure/
│   ├── Billing/
│   ├── Persistence/
│   ├── Pricing/
│   ├── Queue/
│   ├── Reporting/
│   ├── Subscription/
│   ├── Tenancy/
│   └── View/Composers/
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

Legacy `Services`, `Repositories`, and `Payments` folders remain only where needed for backward-compatible migration. New use cases should enter through `Application/*` and concrete integrations should sit behind `Domain/*/Contracts` and `Infrastructure/*`.

## 3. Application Actions

Application Actions represent one business use case. Controllers should coordinate HTTP concerns only.

Representative actions now include:

- `Application\Tenant\Actions\RegisterTenant`
- `Application\Pricing\Actions\SetCountryOverride`
- `Application\Booking\Actions\CreateBooking`
- `Application\Booking\Actions\CreateAdminAppointment`
- `Application\Queue\Actions\AddDirectQueueEntry`
- `Application\Queue\Actions\CallNextQueueEntry`
- `Application\Queue\Actions\TransitionQueueEntry`
- `Application\Reporting\Actions\GetReport`
- `Application\Subscription\Actions\GetSubscriptionOverview`
- `Application\Subscription\Actions\GetSubscriptionUsage`
- `Application\Subscription\Actions\CheckSubscriptionLimit`
- `Application\Subscription\Actions\RequestSubscriptionUpgrade`

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

## 6. Booking and appointment lifecycle

The booking engine contains DTOs, events, exceptions, slot validation and concurrency protection.

```text
Request
  -> Application use case
  -> Domain booking rules
  -> transactional persistence
  -> domain/application event
  -> deferred side effects
```

Admin appointment creation follows the same orchestration boundary. Customer creation, appointment creation and optional queue admission are treated as one transactional application operation.

Concurrent booking protection must remain inside the write transaction. Availability should be revalidated while the relevant records are locked.

## 7. Queue domain

Queue transitions are centralized in explicit Application Actions and backed by repository contracts.

```text
HTTP request
    -> FormRequest
    -> Queue Application Action
    -> Queue repository / domain rules
    -> Tenant DB
```

Direct admission is transactional so customer, appointment and queue creation either succeed together or roll back together.

A controller should not implement queue-state rules or bypass the transition use case.

## 8. Subscription and Billing

Subscription reads and upgrade requests are Application use cases backed by contracts and Infrastructure adapters.

```text
Controller
   -> Subscription Action
   -> Domain contract
   -> Infrastructure adapter
   -> Central billing data
```

Upgrade side effects are emitted as an application event and handled asynchronously by infrastructure listeners. Email/SMS/provider integrations must not be required for the primary database write to succeed.

Payment status must be driven by verified provider events, not browser redirects.

## 9. Payment architecture

Payment gateway selection is separated into a capability contract and provider implementation.

### Gateway contract

`Domain\Shared\Contracts\PaymentGatewayResolver`

### Provider implementation

`Services\PaymentGatewayRouter`

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

Webhook processing must verify signatures, normalize provider payloads, be idempotent, and apply tenant-safe billing state transitions.

## 10. Repository boundary

Repositories expose persistence capabilities through contracts.

```text
Application / Domain
        |
        v
Repository contract
        |
        v
Infrastructure / Eloquent repository
        |
        v
Central or tenant database
```

A repository should not contain business rules. It should load, persist and query data required by a use case.

## 11. Transactions

Application code should depend on `Application\Shared\Contracts\TransactionManager` rather than calling `DB::transaction()` directly when transaction orchestration is part of the use-case boundary.

Laravel's implementation is `Infrastructure\Persistence\LaravelTransactionManager`.

## 12. View composition

`AppServiceProvider` is reserved for dependency wiring and event registration. Database reads needed by Blade layouts belong in dedicated contracts, adapters and view composers.

For example:

```text
Infrastructure\View\Composers\LandingLayoutComposer
Infrastructure\View\Composers\AdminLayoutComposer
```

This keeps presentation data access out of the global service provider.

## 13. Controllers

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
- duplicate state-transition rules;
- perform queued notification side effects synchronously when the primary write can succeed independently.

## 14. State transitions

Appointment, queue, subscription and payment lifecycles should have explicit allowed transitions. State changes must be centralized so an endpoint cannot bypass invariants.

### Appointment

```text
pending -> confirmed -> checked_in -> in_service -> completed
   |            |
   +----------> cancelled / no_show
```

### Queue

```text
waiting -> serving -> completed
    |         |
    +-------> skipped
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

## 15. Authorization

Authorization is layered:

```text
Authentication
   + tenant membership
   + role/permission
   + resource ownership/scope
   = authorization decision
```

A role alone is not sufficient when a resource can cross tenant or user boundaries.

## 16. Events and jobs

Events describe facts; jobs perform deferred work.

Examples:

- `AppointmentCreated`
- `AppointmentStatusChanged`
- `SubscriptionUpgradeRequested`
- `PaymentSucceeded`
- `SubscriptionActivated`
- `TenantCreated`

Jobs handle reminders, exports, reports, email/SMS, reconciliation and cleanup.

## 17. Localization

Locale resolution is infrastructure/application behavior. Views should only consume translation keys.

Every landing/signup key used by Blade must exist in the supported locale files. Translation-key contract checks should protect against schema drift between Blade and `lang/*` files.

## 18. Migration strategy

The refactor is intentionally incremental:

1. Keep externally observable behavior stable.
2. Introduce one Application Action per use case.
3. Move transport-independent rules into Domain.
4. Introduce contracts only where a dependency crosses a boundary.
5. Move concrete integrations into Infrastructure.
6. Retire legacy service/controller paths after equivalence is proven by tests.

Do not rewrite the whole project in a single change.

## 19. Quality gates

Before production:

- all feature/unit tests pass;
- architecture/dependency tests pass;
- tenant isolation tests pass;
- payment webhook idempotency tests pass;
- localization key tests pass;
- PHP static analysis has no new high-severity findings;
- queue/scheduler/failed-job monitoring is configured;
- backups and restore procedures are verified.
