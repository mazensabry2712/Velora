# Velora — Architecture Guide

Velora is a Laravel 12 modular-monolith SaaS. The architecture uses pragmatic Domain-Driven Design and explicit boundaries so the product can stay focused as an MVP while growing into a multi-module business platform.

## 1. Dependency direction

```text
HTTP / Console / Webhooks
          |
          v
   Application layer
    (use cases/actions)
          |
          v
      Domain layer
 (rules/contracts/DTOs/events)
          |
          v
 Infrastructure layer
(Eloquent/tenancy/payments/cache/storage)
```

Dependencies point inward. Domain and Application code must not depend on controllers, Blade views, vendor SDKs, or concrete Infrastructure implementations.

## 2. Application structure

```text
app/
├── Application/
│   ├── Billing/Actions/
│   ├── Booking/Actions/
│   ├── Customer/Actions/
│   ├── Pricing/Actions/
│   ├── Queue/Actions/
│   ├── Staff/Actions/
│   ├── Subscription/Actions/
│   ├── Tenant/Actions/
│   └── Shared/Contracts/
├── Domain/
│   ├── Booking/
│   ├── Billing/
│   ├── Customer/
│   ├── Queue/
│   ├── Shared/Contracts/
│   ├── Staff/
│   ├── Subscription/
│   └── Tenant/
├── Infrastructure/
│   ├── Billing/
│   ├── Booking/
│   ├── Customer/
│   ├── Payments/
│   ├── Persistence/
│   ├── Queue/
│   ├── Staff/
│   ├── Subscription/
│   └── Tenancy/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Repositories/
│   ├── Contracts/
│   └── Eloquent/
└── Services/
    └── legacy/compatibility code being retired incrementally
```

Legacy folders may remain temporarily during migration. New code should use Application Actions, Domain contracts/rules and Infrastructure implementations.

## 3. MySQL and tenancy boundaries

Velora uses MySQL for both central and tenant contexts.

### Central database

```text
Tenants / domains
Subscription plans
Country pricing / tax
Central administration
Billing/provider state
System settings
Platform-level data
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
Tenant operational reports
```

Subscription and platform billing state are central concerns. Operational business data is tenant-scoped.

## 4. Tenancy lifecycle

```text
Request
  -> identify central vs tenant context
  -> initialize tenant when required
  -> authenticate and bind token to tenant
  -> execute Application use case
  -> apply Domain rules
  -> persist through Infrastructure
```

Rules:

1. Do not trust browser-supplied tenant identity when the host/domain already establishes it.
2. Do not query tenant data using the central connection accidentally.
3. Do not expose central records from tenant endpoints.
4. Tenant-aware jobs must preserve tenant context.
5. Cache and uploads should carry tenant scope where required.
6. Tenant API tokens must belong to the active tenant.

## 5. Application Actions and controllers

Application Actions represent one business use case. Controllers coordinate transport concerns only.

```php
public function store(StoreRequest $request)
{
    $result = $this->action->execute($request->validated());

    return response()->json(...);
}
```

Controllers must not contain multi-step business orchestration, provider-specific payment code, duplicated state-transition rules, entitlement rules, or industry-specific business branches.

## 6. Booking and queue

Booking and Queue are first-class MVP business modules.

```text
HTTP
  -> Application Action
  -> Domain rules/contracts
  -> Infrastructure reader/writer
  -> tenant MySQL
```

Booking must revalidate availability inside the write transaction. Queue transitions are explicit and cover adding, advancing/calling, skipping, removing, priority/VIP state, status and overview operations.

## 7. Repository and transaction boundaries

```text
Application/Domain
       |
       v
Repository contract
       |
       v
Infrastructure implementation
       |
       v
MySQL
```

Application use cases should depend on `Application\Shared\Contracts\TransactionManager` when transaction orchestration is part of the use case boundary. Laravel's implementation is `Infrastructure\Persistence\LaravelTransactionManager`.

## 8. Payment architecture

Payment selection is abstracted behind:

`Domain\Shared\Contracts\PaymentGatewayResolver`

The concrete routing implementation is:

`Infrastructure\Payments\PaymentGatewayRouter`

Provider-specific SDK/API code belongs in Infrastructure.

```text
Application Billing use case
          |
          v
PaymentGatewayResolver
          |
          v
PaymentGatewayRouter / Manager
          |
    +-----+-----+------+-----+
    |           |      |     |
 Stripe       Fawry  PayPal Moyasar
```

### MVP gateways

The initial provider set is:

**Stripe + Fawry + PayPal + Moyasar**

Target markets are Egypt, the Middle East/GCC, North America and additional international markets where the Velora merchant entity and provider support the transaction.

This set is a pragmatic starting strategy, not a claim of 100% global payment coverage. Provider eligibility depends on merchant country/legal entity, onboarding, settlement, currency, customer country and payment-method availability.

Official references:

- Stripe: https://stripe.com/global
- Fawry Accept: https://www.fawry.com/ar/online-checkout/
- PayPal country codes: https://developer.paypal.com/reference/country-codes/
- PayPal supported features: https://developer.paypal.com/payouts/supported-features/
- Moyasar: https://moyasar.com/ar/products/accept-payments/
- Moyasar FAQ: https://moyasar.com/ar/resources/faqs/

### Payment invariants

1. Gateway selection is deterministic and merchant/country aware.
2. Payment status changes from verified provider events/webhooks, not browser redirects.
3. Webhook handling is idempotent.
4. Provider authenticity/signature verification is enforced where supported.
5. Provider events are mapped to the correct Velora tenant and subscription before state mutation.
6. Adding a provider requires a new adapter behind the same boundary.

## 9. Subscription and billing

```text
Application billing action
        |
        v
Domain billing/subscription contracts
        |
        v
Infrastructure billing implementation
        |
        v
Central MySQL
```

Keep tenant operational data, platform subscription state, payment transaction state and country pricing/currency concerns separate.

## 10. Authorization

```text
Authentication
  + tenant membership
  + role/permission
  + resource ownership/scope
  = authorization decision
```

Token-to-tenant binding is part of the tenant API security boundary.

## 11. State transitions

```text
Appointment: pending -> confirmed -> checked_in -> in_service -> completed
             cancelled / no_show where allowed

Subscription: trial -> active -> grace -> expired
             active -> cancelled

Payment: pending -> processing -> paid
         failed / cancelled
         paid -> refunded
```

State transitions must remain explicit and centralized.

## 12. Events, jobs and localization

Events describe business facts. Jobs perform deferred work such as reminders, exports, reports, email/SMS, reconciliation and cleanup.

Views consume translation keys only. Landing/signup translation keys are protected by tests to prevent translation schema drift.

## 13. Platform-core direction

Velora is intentionally evolving from a booking-focused SaaS into a reusable business-management platform.

```text
                    VELORA PLATFORM
                          |
          +---------------+---------------+
          |                               |
      Platform Core                 Business Modules
          |                               |
  Tenancy / Auth / Billing        Booking / Queue / CRM
  Users / Permissions              HR / Inventory / POS
  Localization / Settings         Sales / Finance / Support
  Notifications / Audit           Projects / vertical modules
```

The architecture remains a modular monolith. Microservices are not an MVP requirement.

## 14. MVP product scope

The MVP uses one primary subscription price and focuses on a small, useful capability set.

```text
Platform Core
├── Multi-tenancy
├── Authentication
├── Users
├── Roles & permissions
├── Billing / subscriptions
├── Localization
├── Notifications
├── Settings
└── Audit foundation

Business capabilities
├── Customers
├── Staff
├── Services
├── Booking
├── Queue
└── Reports
```

The MVP is not a full ERP, CRM, HR or POS suite.

## 15. Module registry and industry presets

Future business capabilities are modules, not separate applications.

```text
Business type
    -> industry preset
    -> recommended modules
    -> tenant customization
    -> enabled modules
    -> workspace
```

A module registry should own module metadata, permissions, navigation, dashboard widgets, settings, dependencies, entitlement requirements and event registrations.

Avoid scattering conditions such as `if ($tenant->industry === 'clinic')` through the application. Industry should influence configuration, not duplicate business logic.

## 16. Commercial model

The MVP uses one primary subscription price. Future monetization can add paid modules and add-ons only after validating demand.

```text
Tenant
  -> Core subscription
  -> Included MVP capabilities
  -> Optional future modules / add-ons
```

Potential future add-ons include CRM, HR, Inventory, POS, Finance, API access, advanced automation, storage and messaging usage.

## 17. Growth roadmap

```text
MVP
  Core + Customers + Staff + Booking + Queue + Reports

V2
  CRM

V3
  HR

V4
  Inventory + Sales

V5
  Finance / ERP

Later
  POS + Projects + Support + industry-specific modules
```

Every new module should reuse the existing tenant, identity, authorization, billing, localization, notification and audit foundations.

## 18. Incremental migration strategy

1. Keep existing behavior stable.
2. Introduce an Application Action for one use case.
3. Move transport-independent rules into Domain.
4. Introduce contracts where dependencies cross boundaries.
5. Move concrete persistence/integrations into Infrastructure.
6. Retire legacy service/controller paths only after equivalence is proven.
7. Add new capabilities as modules instead of growing a monolithic service/controller layer.

Do not rewrite the entire platform in one migration.

## 19. Quality gates

Before production:

- all feature/unit tests pass;
- architecture/dependency tests pass;
- tenant isolation tests pass;
- token-to-tenant binding tests pass;
- payment webhook idempotency tests pass;
- payment provider selection tests cover merchant/country rules;
- localization key tests pass;
- static analysis has no new high-severity findings;
- queue/scheduler/failed-job monitoring is configured;
- backups and restore procedures are verified;
- module enablement cannot bypass authorization or subscription entitlements.
