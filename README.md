# Velora

Velora is a multi-tenant appointment-booking SaaS built with Laravel.

It provides businesses with online booking, staff scheduling, queue management, customer management, reports, administration and subscription billing.

## Core Stack

- Laravel 12
- PHP 8.2+ (dependency platform currently pinned to PHP 8.4)
- Blade
- Tailwind CSS 4
- Vite
- MySQL / tenant databases
- Stancl Tenancy
- Laravel Sanctum
- Spatie Permission
- Stripe
- Moyasar

## Core Capabilities

- Multi-tenant business accounts.
- Public booking page.
- Services and staff management.
- Working days and time slots.
- Appointment lifecycle management.
- Queue / waiting-room management.
- Customer management.
- Admin dashboard.
- Reports and exports.
- Tenant settings and localization.
- Subscription plans and usage limits.
- Trial / active / grace / expired / cancelled subscription lifecycle.
- Stripe checkout / portal support.
- Moyasar payment flow support.
- Background commands for analytics, subscriptions and reminders.

## Documentation

The `docs/` directory is the engineering source of truth for project readiness and execution.

| Document | Purpose |
| --- | --- |
| `docs/PROJECT_STATUS.md` | What is already implemented and what remains. |
| `docs/ARCHITECTURE.md` | Architecture and engineering rules. |
| `docs/PRODUCTION_ROADMAP.md` | Phased roadmap from current state to production. |
| `docs/SECURITY_TENANCY_AUDIT.md` | Tenant isolation, authorization and security audit checklist. |
| `docs/BILLING_HARDENING.md` | Billing/webhook/subscription hardening plan. |
| `docs/TESTING_QA_PLAN.md` | Automated testing, security testing and release QA plan. |
| `docs/PRODUCTION_CHECKLIST.md` | Final go/no-go production checklist. |
| `docs/CHANGELOG_IMPLEMENTATION.md` | Living record of important implementation work. |

## Development

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm ci
```

Create local environment and application key using the standard Laravel setup for this repository, then configure the central database, tenant database settings, mail, queue, storage and payment providers.

Run tests:

```bash
php artisan test
```

Build frontend assets:

```bash
npm run build
```

## Production Readiness

Velora has a strong core SaaS implementation, but a production launch must not be considered complete until the P0/P1 items in the documentation are verified.

The highest-risk areas are:

1. Cross-tenant isolation.
2. Resource-level authorization.
3. Payment webhook correctness and idempotency.
4. Public endpoint abuse protection.
5. Storage quota enforcement.
6. Production monitoring, backup and rollback readiness.

Read `docs/PRODUCTION_ROADMAP.md` first when continuing the project.

## Engineering Rule

Do not mark a feature complete because its happy path works. A feature is complete only when its authorization, tenant isolation, validation, failure behavior, concurrency behavior, automated tests and production operation are verified.

## License

This project is proprietary unless a separate license says otherwise.
