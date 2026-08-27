# Velora — Product & Platform Strategy

## 1. Vision

Velora is intended to evolve from a booking-focused SaaS into a reusable business-management platform.

The product principle is:

> One platform that is useful today and extensible tomorrow.

The MVP must be commercially usable on its own while the architecture remains capable of adding CRM, HR, ERP, POS, Inventory, Finance, Projects, Support and industry-specific modules later.

Velora must not become a collection of separate products. A tenant should be able to start with a focused workspace and add modules without migrating to a new system.

## 2. MVP positioning

The MVP is positioned as:

**Velora — Business Management Platform for Small Businesses**

It is not positioned as a full ERP, full CRM or industry-specific suite at launch.

The initial promise is simple business operations from one workspace:

```text
Customers
Staff
Services
Booking
Queue
Reports
Billing
Notifications
Settings
```

## 3. Platform model

Velora has three conceptual levels:

```text
Platform Core
    |
    +--> Business Modules
    |
    +--> Industry Presets
```

### Platform Core

Capabilities shared by every tenant:

- authentication and identity;
- multi-tenancy;
- users;
- roles and permissions;
- subscriptions and billing;
- localization;
- notifications;
- settings;
- audit foundations;
- files and shared platform services as they mature.

### Business Modules

Business capabilities that can be enabled for a tenant:

- Customers;
- Staff;
- Booking;
- Queue;
- CRM;
- HR;
- Inventory;
- Sales;
- Finance;
- POS;
- Projects;
- Support;
- future industry-specific modules.

### Industry Presets

Industry is used to recommend a starting configuration. It must not create a separate codebase or hard-coded business branch.

```text
Industry
   -> preset
   -> recommended modules
   -> tenant selection/customization
   -> enabled modules
   -> workspace
```

## 4. Onboarding experience

The intended onboarding flow is:

```text
Create account
      |
      v
Choose business type
      |
      v
Velora recommends modules
      |
      v
Tenant confirms/customizes
      |
      v
Workspace is created
```

Example:

```text
Business type: Salon

Recommended
✓ Customers
✓ Staff
✓ Booking
✓ Queue

Optional
○ CRM
○ POS
○ Inventory
○ HR
```

The same platform can support a different configuration for a clinic, gym, restaurant, education business or professional-services company.

## 5. Core product rule

The **industry is not the product**.

A tenant may choose a different module combination from the industry recommendation when the subscription and module rules allow it.

Avoid application-wide logic such as:

```php
if ($tenant->industry === 'clinic') {
    // clinic-only business behavior
}
```

Prefer module capabilities and tenant configuration.

## 6. MVP subscription model

The MVP intentionally uses **one primary subscription price**.

The first commercial model is:

```text
Tenant
  |
  +--> One Velora subscription
  |
  +--> Core + initial business capabilities
  |
  +--> Future paid modules/add-ons
```

The MVP does not use Starter/Business/Enterprise tiers merely to split features that have not yet been validated commercially.

When product demand is validated, Velora can introduce paid modules and add-ons such as:

- CRM;
- HR;
- Inventory;
- POS;
- Finance;
- API access;
- advanced automation;
- additional storage;
- messaging/SMS usage.

## 7. Module marketplace / add-on direction

Long term, tenants should be able to see available modules from inside their workspace.

Example:

```text
Your Velora Workspace

✓ Core
✓ Booking
✓ Queue

Available modules

CRM        Add module
HR         Add module
Inventory  Add module
POS        Add module
Finance    Add module
```

Enabling a module must also enable its permissions, navigation, settings, entitlements and relevant dependencies through the module registry.

## 8. Module registry

The platform should eventually expose a registry where every module can declare:

- key/name;
- display metadata;
- permissions;
- routes/endpoints;
- navigation entries;
- dashboard widgets;
- settings;
- dependencies;
- subscription entitlement requirements;
- event/listener registrations.

This keeps module enablement centralized and prevents feature discovery from spreading through controllers and views.

## 9. Industry presets

Suggested initial presets:

```text
Salon
  -> Customers + Staff + Booking + Queue

Clinic
  -> Customers/Patients + Staff + Booking + Queue + Billing

Gym
  -> Customers + Staff + Booking

Restaurant
  -> Customers + Staff + POS + Inventory + Reservations (future)

Education
  -> Students/Customers + Staff + Scheduling + Attendance (future)

Professional Services
  -> Customers + Staff + Booking + CRM (future)
```

These are recommendations, not isolated products.

## 10. Payment-market strategy

Velora's first target markets are:

1. Egypt;
2. Middle East / GCC;
3. North America;
4. other international markets where the Velora merchant entity and payment provider support the transaction.

### Initial gateway set

The MVP uses four gateways:

```text
Stripe + Fawry + PayPal + Moyasar
```

#### Stripe

Primary international payment route where Stripe supports the Velora merchant business.

Stripe's official global availability page states that supported businesses can sell to customers globally, but Stripe business availability is country/region dependent.

Official reference: https://stripe.com/global

#### Fawry

Egypt-focused local payment route. Fawry Accept currently advertises cards, Fawry Pay/reference payments, mobile wallets and recurring payment capabilities.

Official reference: https://www.fawry.com/ar/online-checkout/

#### PayPal

International fallback/additional payment choice with broad country coverage. PayPal documents country-specific feature availability for its APIs.

Official references:

- https://developer.paypal.com/reference/country-codes/
- https://developer.paypal.com/payouts/supported-features/

#### Moyasar

Saudi-focused regional payment route. Moyasar currently supports methods including Mada, Visa, Mastercard, American Express, STC Pay, Apple Pay and Samsung Pay, and states that its service is currently focused on Saudi Arabia.

Official references:

- https://moyasar.com/ar/products/accept-payments/
- https://moyasar.com/ar/resources/faqs/

### Coverage statement

The four gateways are a pragmatic starting set for Egypt + GCC/Middle East + North America/international demand.

They **must not** be marketed as 100% worldwide coverage. Payment availability depends on:

- Velora merchant country and legal entity;
- provider onboarding eligibility;
- settlement country/currency;
- customer country;
- local payment method availability;
- provider-specific restrictions and compliance.

### Gateway architecture rule

All gateways must implement the same Velora payment boundary.

```text
Application / Billing use case
            |
            v
PaymentGatewayResolver
            |
            v
Gateway manager / router
            |
   +--------+--------+--------+
   |        |        |        |
 Stripe   Fawry   PayPal   Moyasar
```

A new gateway must be added as a new adapter/implementation, not by adding provider-specific logic to Domain rules or controllers.

## 11. Future product evolution

The intended growth order is:

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

The order can change according to customer demand. The architectural rule does not change: new modules reuse the existing platform core.

## 12. CRM direction

CRM is the first major expansion because it can be useful across almost every industry.

Initial CRM capabilities can include:

- leads;
- companies;
- contacts;
- deals/opportunities;
- activities;
- tasks;
- notes;
- tags;
- communication history;
- customer segmentation.

CRM should reuse the existing customer/user/notification foundations rather than create a separate identity system.

## 13. HR direction

HR can later provide:

- employees;
- departments;
- attendance;
- leave;
- contracts;
- payroll;
- performance.

HR must reuse the platform's identity, authorization, tenant and notification foundations.

## 14. ERP direction

ERP should be added only after the platform has stable module/entitlement foundations.

Potential ERP domains:

- products;
- inventory;
- purchasing;
- suppliers;
- sales;
- invoices;
- expenses;
- finance/accounting.

ERP is intentionally a later phase because it introduces substantially more complex business rules than the MVP.

## 15. UX principle

The tenant should see a business workspace, not the underlying architecture.

The sidebar and dashboard are generated from enabled capabilities.

Example clinic workspace:

```text
Dashboard
Customers / Patients
Appointments
Queue
Staff
Billing
Reports
Settings
```

Example restaurant workspace in a later phase:

```text
Dashboard
Customers
Orders
POS
Tables
Reservations
Inventory
Purchasing
Staff
Reports
Settings
```

## 16. Non-goals for the MVP

Do not build all future modules now.

The MVP should not attempt to fully implement:

- complete ERP;
- complete payroll/HR suite;
- complete CRM suite;
- advanced accounting;
- large-scale POS;
- microservices architecture.

The goal is a small product that can be sold, operated and validated.

## 17. Architecture success criteria

The platform strategy is successful when:

1. a new tenant can subscribe without knowing internal architecture;
2. the tenant can select an industry and receive useful defaults;
3. the tenant can enable a supported module without changing tenant identity or migrating data;
4. billing and entitlements control module access;
5. permissions are registered with modules;
6. controllers do not contain industry-specific branches;
7. provider-specific payment logic remains in Infrastructure;
8. a future module can be added without rewriting existing modules;
9. all existing tests remain green after each incremental expansion.
