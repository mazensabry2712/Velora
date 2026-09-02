# Velora — Future Architecture

## Target direction

Velora remains a **Laravel modular monolith**. The goal is not to split the system into microservices prematurely; the goal is to establish boundaries strong enough that future modules can be added without duplicating the platform core.

## High-level model

```text
                         VELORA PLATFORM
                                |
              +-----------------+-----------------+
              |                                   |
        PLATFORM CORE                       BUSINESS MODULES
              |                                   |
    +---------+---------+             +-----------+-----------+
    |         |         |             |           |           |
 Tenancy   Identity  Billing      Booking      Queue      Customer
    |         |         |             |           |           |
 Settings  Auth/RBAC  Entitlements Availability  Workflow   CRM
    |         |         |             |           |           |
 Localization Notifications        Staff/Service future CRM/HR
 Audit      Integrations             Scheduling   modules
```

## Platform Core

Platform Core owns cross-cutting capabilities that every module should reuse:

- Tenant lifecycle and tenant isolation.
- Authentication and API token boundaries.
- Roles, permissions and resource authorization.
- Subscription entitlements and usage limits.
- Localization and locale persistence.
- Notification delivery contracts and delivery state.
- Shared files/storage policy.
- Audit/activity infrastructure.
- Shared settings and configuration.
- Shared domain contracts, transactions and infrastructure adapters.

A business module must not create a second implementation of these capabilities.

## Current business modules

### Customer

Owns the booking-facing customer entity, lifecycle data, GDPR state, customer history and optional account linkage.

### Staff

Owns staff identity, service assignment, availability, working hours, breaks, time-off and commission configuration.

### Booking

Owns services, appointments, availability rules, recurring bookings and booking-specific domain policies.

### Queue

Owns waiting-room state, queue ordering, turn transitions and queue lifecycle notifications.

### Billing

Owns billing use cases and tenant-facing billing records while delegating provider integration to Infrastructure.

## Future modules

The current roadmap can grow as:

```text
MVP
  Customer + Staff + Booking + Queue + Reports

V2
  CRM

V3
  HR

V4
  Inventory + Sales

V5
  Finance / ERP

Later
  POS + Projects + Support + vertical-specific modules
```

The modules share the same Platform Core and tenant boundary. They are not separate applications.

## Module contract

Each mature module should converge toward:

```text
Module/
  Domain/
    Entities
    ValueObjects
    Contracts
    Events
    Exceptions
  Application/
    Actions
    DTOs
    Queries
  Infrastructure/
    Persistence
    Integrations
  Http/
    Controllers
    Requests
    Resources
  Providers/
  routes.php
```

The existing `app/Application`, `app/Domain`, `app/Infrastructure` layout remains valid during the transition. A physical per-module directory split should happen only after dependency boundaries are stable and the migration cost is justified.

## Dependency rule

```text
HTTP / Console / Jobs
        |
        v
Application
        |
        v
Domain contracts + rules
        |
        v
Infrastructure
        |
        v
Database / external providers
```

Domain and Application code must not import controllers, Blade views or provider SDKs directly.

## Data ownership

Each business concept has one canonical owner.

```text
Customer identity      -> customers
Staff identity         -> staff
Staff availability     -> staff_working_hours
Staff/service link     -> staff_services.staff_id
Appointment customer   -> appointments.customer_id_new (transition name)
Appointment staff      -> appointments.staff_id_new (transition name)
```

The `_new` appointment names are temporary migration-era names. They must eventually become `customer_id` and `staff_id` only after historical data reconciliation and all code consumers are migrated.

## Identity separation

`User` represents authentication/system identity. `Customer` and `Staff` represent business identities.

```text
User
 ├── authentication
 ├── roles/permissions
 └── optional business-profile link

Customer
 ├── booking history
 ├── lifecycle/GDPR
 └── optional User account

Staff
 ├── scheduling
 ├── services
 └── optional User account
```

This separation prevents the same database column from changing semantic meaning depending on the endpoint.

## Migration strategy

Database evolution follows the same ownership model:

```text
old state
  -> add canonical representation
  -> backfill deterministically
  -> detect unresolved data
  -> switch runtime to canonical representation
  -> add constraints/indexes
  -> remove legacy representation
  -> rename canonical fields to stable domain names
```

Existing deployed migrations remain immutable. Cleanup occurs through forward migrations.

## Package strategy

Use Laravel-native mechanisms when they already solve the requirement. Use established packages when they replace a real custom subsystem and provide a better maintained abstraction.

Examples already accepted in the architecture:

- Sanctum for API token abilities.
- Spatie Permission for roles/permissions.
- Stancl Tenancy for tenant initialization/lifecycle.
- Laravel RateLimiter for request throttling.

Planned package migrations require the package, lockfile, data migration, application refactor and regression coverage to land as one coherent change.

## Industry presets and modules

Business type must select configuration/modules rather than create code forks:

```text
business type
   -> industry preset
   -> recommended modules
   -> tenant configuration
   -> entitlements
   -> enabled modules
```

Avoid application-wide conditionals such as `if ($tenant->industry === 'clinic')` when the distinction can be represented by module configuration or policy.

## Non-goals

- No microservices split for MVP.
- No replacement of working custom business rules with packages merely for abstraction purity.
- No rewriting of deployed migration history.
- No shared database columns whose meaning changes between legacy and canonical identities.
