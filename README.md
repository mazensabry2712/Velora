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
| `docs/MASTER_QA_EXECUTION_RUNBOOK.md` | Exact Master QA execution method, evidence rules, handoff procedure and continuation order. |
| `docs/QA_FINDINGS_LOG.md` | Defect-by-defect findings, root causes, fixes and regression guards discovered by Master QA. |
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

Run the Master QA scenarios against the canonical MySQL CI environment:

```bash
php artisan test tests/Feature/QA --compact
```

Build frontend assets:

```bash
npm run build
```

## QA Continuation Rule

Master QA follows a strict cycle:

```text
inspect current main
→ verify current CI evidence
→ identify first confirmed discrepancy
→ write/adjust regression test
→ diagnose root cause
→ apply minimal correct fix
→ run focused regression
→ run MySQL Master QA CI
→ document finding/fix/status
→ continue to next gate
```

Do not declare a feature family certified from test count alone. Certification requires business-flow correctness, database/reconciliation checks, security/authorization, concurrency where relevant, and fresh MySQL CI evidence on the current `main` SHA.

See `docs/MASTER_QA_EXECUTION_RUNBOOK.md` for the complete operational method.
