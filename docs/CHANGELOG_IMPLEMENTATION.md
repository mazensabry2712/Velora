# Velora — Implementation History & Work Log

## Purpose

This file is the living record of the important work already performed and the engineering work that should be performed next.

## Application Page Inventory

### Public / Landing — Central Domain

- `/` — Landing / marketing page.
- `/pricing` — Pricing page.
- `/signup` — Tenant signup.
- `/signup/provisioning/{token}` — Signup provisioning page.
- `/signup/provisioning/{token}/status` — Provisioning status endpoint/page contract.
- `/signup/provisioning/{token}/resend` — Verification resend action.
- `/email/verify/{token}` — Tenant email verification.
- `/login` — Central find-account / tenant discovery page.
- `/{locale}`, `/lang/{locale}`, `/region/{locale}/{country}`, `/currency/{currency}` — public locale/region/currency switching routes.

### Tenant Public / Customer

- `/` — Tenant root redirect to booking.
- `/book` — Public booking page.
- `/queue` — Queue status redirect.
- `/queue/status` — Public queue status.
- `/my-queue` — Authenticated customer's queue page.
- `/login` — Tenant login page.
- `/change-language/{lang}` — Tenant language switch and authenticated-user locale persistence.

### Tenant Authentication

- `/login` — Tenant login UI.
- `/api/auth/login` — Tenant login API.
- `/api/auth/logout` — Tenant logout API.
- Forgot Password / Reset Password — not implemented as a full Tenant-facing web flow yet; current Login UI intentionally exposes a disabled placeholder rather than a fake route.

### Tenant Admin / Workspace

- `/admin/dashboard` — Admin dashboard.
- `/admin/onboarding` — Initial workspace onboarding.
- `/admin/profile` — Profile management.
- `/admin/appointments` — Appointment management.
- `/admin/staff` — Staff management.
- `/admin/customers` — Customer management.
- `/admin/queue` — Queue day listing.
- `/admin/queue/{date}` — Queue for a specific date.
- `/admin/queue/{date}/print` — Printable queue view.
- `/admin/queue/export-excel` — Queue export action.
- `/admin/reports` — Reports.
- `/admin/settings` — Tenant settings.
- `/admin/assistants` — Assistant management.
- `/admin/subscription` — Subscription overview.
- `/admin/subscription/billing` — Subscription billing.
- `/admin/subscription/upgrade` — Upgrade flow.

### Tenant Billing / Payment Return Pages

- `/billing/expired` — Expired subscription page.
- `/billing/success` — Billing success page.
- `/billing/moyasar/pay` — Moyasar payment entry.
- `/billing/moyasar/callback` — Moyasar callback/return.
- Stripe and Moyasar webhook endpoints exist separately and are API/webhook endpoints, not user-facing pages.

### Tenant APIs Behind the Pages

The Tenant application also exposes API routes for appointments, services, timeslots, working days, staff, queue operations, customers, settings and invoices. These are supporting endpoints rather than separate web pages and should not be counted as additional UI screens unless a dedicated view exists.

### Super Admin — Intentionally Outside Tenant Language Scope For Now

- `/super-admin/login`
- `/super-admin/dashboard`
- `/super-admin/tenants`
- `/super-admin/subscription-plans`
- `/super-admin/activity-logs`
- `/super-admin/settings`
- `/super-admin/notifications`
- `/super-admin/analytics`
- `/super-admin/reports`
- `/super-admin/kpis`
- `/super-admin/upgrade-requests`
- `/super-admin/upgrade-requests/{id}`
- `/super-admin/countries`
- `/super-admin/country-pricing`
- `/super-admin/promo-codes`
- `/super-admin/lang/{locale}` — currently limited to the Super Admin's own central locale behavior.

## Language Scope

The 15 supported platform locales are:

`ar`, `en`, `fr`, `es`, `de`, `it`, `pt`, `ru`, `zh`, `ja`, `tr`, `hi`, `ko`, `nl`, `id`.

The Tenant/Public language scope covers Public/Landing, Tenant Customer, Tenant Authentication, Tenant Admin and Tenant Billing surfaces. Super Admin localization remains a separate scope for now.

## Already Implemented / Observed

### Architecture

- Multi-tenant Laravel SaaS architecture exists.
- Domain-oriented booking services/DTOs/events/exceptions exist.
- Tenant routing uses Stancl Tenancy middleware.
- Tenant-aware cache/filesystem/queue bootstrapping is configured.

### Booking

- Public booking page exists.
- Service/staff availability endpoints exist.
- Time-slot validation was added and iterated on.
- Staff scheduling/time normalization was improved.
- Booking creation has dedicated domain service logic.
- Appointment events and slot-unavailable exception exist.

### Queue

- Public queue APIs exist.
- Admin queue operations exist.
- Call-next, serve, complete and return-to-waiting flows exist.
- Appointment/queue integration tests exist.

### Administration

- Admin dashboard exists.
- Appointments, customers, staff, reports and settings areas exist.
- Admin onboarding exists.
- Profile management exists.
- Assistant management exists.

### Subscription / Billing

- Subscription dashboard restructuring was performed.
- Duplicate subscription-dashboard controller responsibility was reduced.
- Subscription usage information exists.
- Stripe customer/price data is supported.
- Trial extension flow exists.
- Billing portal/checkout routes exist.
- Moyasar routes/callback exist.
- Trial/grace/expired subscription enforcement exists.
- Founder trial alerts exist.

### Settings / Localization

- Settings structure was rebuilt and split into partial views.
- Arabic business name support was added to the rendered settings form.
- The platform UI locale registry contains the same supported language set used by the Landing: `ar`, `en`, `fr`, `es`, `de`, `it`, `pt`, `ru`, `zh`, `ja`, `tr`, `hi`, `ko`, `nl`, `id`.
- Arabic is the current public/default fallback, but the public default is now read from the central `SystemSetting` key `public_default_locale`, allowing a future Super Admin language selector to change the public default without hard-coding Arabic into application behavior.
- Explicit locale routes, persisted public session/cookie state and the configurable public default continue to drive the central public experience.
- Tenant email-verification pages resolve locale from the persisted tenant language.
- Arabic verification rendering supports RTL and the Velora-branded verification page.

### Tenant Language Lifecycle

- The language selected during Signup is persisted as the Tenant default language.
- If Signup does not explicitly provide a language, the Tenant inherits the current central public default language rather than a hard-coded English default.
- Before a user is authenticated, tenant Provisioning/Verification resolves from the Tenant default language.
- Tenant users have a persistent `locale` preference stored in the tenant `users` table.
- The first Tenant Admin created after email verification is initialized with the Tenant default language as its persisted `User.locale` unless an existing user locale is already set.
- After authentication, persisted `User.locale` takes precedence over session and Tenant default locale.
- Changing language from a tenant-domain language control updates the authenticated User's persisted locale and the current session.
- The persisted User locale survives logout/login and does not silently revert to the Tenant default.
- All platform UI locales remain available to tenants; `Setting.available_languages` is not used to block a supported UI locale.
- Existing Tenant defaults are not rewritten when the central public default changes.
- Super Admin localization remains intentionally outside this Tenant language system for now.

### Authentication / Signup / Tenant Handoff

- Signup creates the central Tenant and Domain before tenant workspace provisioning.
- Signup normalizes registration values and performs Tenant + Domain creation atomically within the registration transaction.
- Verification email is sent during signup and the verification token is hashed/encrypted with an expiry.
- Tenant provisioning no longer creates the first Admin before email verification.
- Verification is the hard gate for creation/activation of the first Tenant Admin.
- Verified Tenant Admin creation is idempotent and consumes the temporary provisioning password after account creation.
- Handoff requires a ready workspace, verified tenant email, an existing verified tenant user and a valid one-time provisioning token.
- Tenant login rejects unverified accounts.
- Verification, provisioning and handoff flows have dedicated regression coverage.
- Tenant Login persists locale on the User and carries that locale into the authenticated session.
- Tenant Login API responses were aligned with the translation catalog instead of returning English-only messages.

### Authentication UI / Frontend Consistency

- A shared `public/css/velora-auth.css` layer now consumes the official Velora brand tokens for authentication screens.
- Tenant Login and Super Admin Login use the same responsive auth shell, official Velora gradient, typography, theme handling and RTL/LTR behavior.
- Tenant Login and Super Admin Login use the Velora logo asset and no longer define independent legacy Indigo brand palettes.
- The email-verification completion screen uses the shared auth visual language and `logo-bais.png`.
- Find Account inherits the same Velora background, surfaces, typography, borders and primary-action styling through the shared auth layer without changing its existing JavaScript or routing behavior.
- Auth theme persistence uses the shared `velora-theme` storage key on the updated auth screens.
- Tenant Login now uses the locale `messages.*` catalog for its core copy and exposes a single responsive language dropdown.
- Login UI direction and locale behavior are covered by `TenantLoginLocaleUiTest`.

### Translation Coverage

- All 15 supported locales now have real translated `auth.php` and `booking.php` core bundles.
- All 15 supported locales have valid direction metadata (`rtl` for Arabic; `ltr` for the remaining supported locales).
- `messages.php` coverage was expanded for all supported tenant locales.
- `notifications.php`, `pagination.php`, `passwords.php` and `validation.php` have been expanded for supported locales as core translation bundles.
- `SupportedLocaleCoreCoverageTest` now checks core bundle existence and notification key/placeholder parity for the supported locales.
- Translation bundle coverage is a core infrastructure milestone, not a claim that every Tenant-facing screen is fully translated.
- Remaining translation work includes feature-specific dashboard/admin copy, hard-coded Blade/JavaScript strings, emails and generated documents.

### Signup E2E / Frontend Contract

- `SignupClientFlowTest` covers the client-facing signup lifecycle including validation, duplicates, locale selection/defaulting, verification, expiry, resend throttling, and machine-readable JSON contract behavior.
- `SignupUiContractTest` verifies the Signup page structure, supported locales, language direction, required fields, CSRF protection, and safe validation redisplay behavior.
- `SignupTenantHandoffTest` covers unverified access, successful verification and tenant handoff behavior.
- `TenantEmailVerificationGateTest` verifies the Admin security gate and explicit Signup language/default inheritance.
- `TenantVerificationLocaleTest` verifies tenant-language and explicit-language override behavior for the verification page using a real tenant database context.
- `TenantEmailVerificationTest` covers verification route/mail/tenant provisioning metadata.

### Testing

- Feature tests cover booking, appointments, queue, billing, localization and other administration areas.
- Dedicated test base classes exist for tenant and super-admin scenarios.
- `SupportedLocaleCoreCoverageTest` verifies core translation bundle existence, locale direction and notification key/placeholder parity for supported locales.
- `TenantLoginLocaleUiTest` verifies Tenant Login localization behavior and UI contract across supported locales.
- Last confirmed user-reported local full suite: **537 tests, 4311 assertions, 0 failures, 0 errors**.

## Important Risks Identified

### P0

1. Tenant isolation must be verified for every explicit central/tenant database access.
2. Payment-provider webhooks must be the source of truth and must be idempotent.
3. Object-level authorization must be verified for all ID-based resources.

### P1

4. Storage quota currently needs real usage tracking.
5. Billing history should be evaluated against formal invoice requirements.
6. Public endpoints need explicit abuse/rate-limit verification.
7. Subscription state transitions should be decoupled from normal requests where possible.
8. Database indexes/performance should be reviewed under realistic tenant volume.
9. Real email delivery needs external SMTP/provider validation; local Mailtrap attempts have previously depended on queue/SMTP configuration.

### P2

10. Dashboard analytics should be refactored and optimized further.
11. Remaining hard-coded translations should be localized.
12. Browser/mobile/RTL visual QA should be completed across the Tenant language lifecycle.
13. Forgot Password / Reset Password should be implemented as a complete Tenant-aware flow.
14. Generated emails/PDFs/documents should be reviewed for locale completeness.
15. Production operations/documentation should be completed.

## Execution Rule

Every completed task should update this file with:

- Date.
- Scope.
- Files/areas changed.
- Tests run.
- Result.
- Any follow-up risk.

## Latest Implementation Entry

### 2026-08-29 — Application Page Inventory & Scope Clarification

Scope:
- Document the current user-facing page inventory for Public/Landing, Tenant Customer, Tenant Authentication, Tenant Admin, Tenant Billing and Super Admin.
- Explicitly distinguish web pages from supporting API endpoints.
- Clarify that the 15-locale Tenant localization scope excludes Super Admin for now.
- Record the current missing authentication screen: a complete Tenant Forgot/Reset Password flow.

Changed:
- `docs/CHANGELOG_IMPLEMENTATION.md`

Validation:
- Inventory was reconciled against the current central and tenant route definitions.
- Last user-confirmed full suite remains **537 tests, 4311 assertions, 0 failures, 0 errors**.

Follow-up:
- Complete Tenant Forgot/Reset Password.
- Continue page-by-page Tenant localization and hard-coded string audit.
- Perform browser/mobile/RTL visual QA over the documented page inventory.

## Previous Implementation Entry

### 2026-08-28 — Tenant Language Persistence & Configurable Public Default

Scope:
- Make the Landing's current default language configurable rather than permanently hard-coded to Arabic.
- Use the current public default when a Signup language is not explicitly selected.
- Persist Tenant user language choices so a user's manual language change survives logout/login.
- Ensure authenticated User locale overrides session and Tenant default locale.
- Allow every platform UI locale exposed by the Landing to remain available throughout the Tenant application.
- Initialize the first verified Tenant Admin with the Tenant default language unless a persisted User locale already exists.
- Keep Super Admin localization outside the Tenant language system for now.

Changed:
- `app/Http/Middleware/LocaleSignalDetector.php`
- `app/Http/Middleware/SetTenantLocale.php`
- `app/Http/Controllers/Auth/TenantAuthController.php`
- `app/Http/Controllers/Auth/TenantProvisioningController.php`
- `app/Models/User.php`
- `database/migrations/tenant/2026_08_28_000001_add_locale_to_users_table.php`
- `app/Services/TenantRegistrationService.php`
- `routes/tenant.php`
- `tests/Feature/TenantEmailVerificationGateTest.php`
- `docs/CHANGELOG_IMPLEMENTATION.md`

Behavior:
- Current public default fallback is `ar`.
- Future Super Admin setting key: `public_default_locale`.
- Signup language, when supplied and supported, becomes the Tenant default.
- Signup without a language inherits `public_default_locale`.
- Tenant user locale precedence is: persisted User locale → explicit request/session override → Tenant default → application fallback.
- Authenticated tenant language changes update both the User's persisted locale and the active session.
- Existing Tenants are unaffected by future changes to `public_default_locale`.
- `available_languages` is not used to remove a supported platform UI locale.
- The first verified Tenant Admin starts with `User.locale` equal to the Tenant language and can subsequently override it by explicitly changing language.

Validation:
- The user successfully migrated all existing tenant databases with `php artisan tenants:migrate --force`.
- The targeted `TenantEmailVerificationGateTest` passed: **4 tests, 10 assertions**.
- The later expanded Authentication/Signup regression sequence culminated in the user-reported green full suite of **537 tests, 4311 assertions, 0 failures, 0 errors**.
- Browser validation is still required for all supported languages, including RTL and non-Latin languages.

Follow-up:
- Complete the documented page-by-page Tenant localization audit.
- Add/expand automated regression coverage for a manual Tenant language change surviving logout/login.
- Add the future Super Admin `Public Default Language` control against `SystemSetting::get/set('public_default_locale', ...)`.
- Continue the documented P0 tenant-isolation and object-level authorization audit.
