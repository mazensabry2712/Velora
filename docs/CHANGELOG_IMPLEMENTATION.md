# Velora — Implementation History & Work Log

## Purpose

This file is the living record of important work already performed and engineering work that should be performed next.

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
- Arabic is the current public/default fallback, but the public default is read from the central `SystemSetting` key `public_default_locale`, allowing a future Super Admin language selector to change the public default without hard-coding Arabic into application behavior.
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
- Verification email is sent during signup and the verification token is hashed/encrypted with an expiry.
- Tenant provisioning no longer creates the first Admin before email verification.
- Verification is the hard gate for creation/activation of the first Tenant Admin.
- Verified Tenant Admin creation is idempotent and consumes the temporary provisioning password after account creation.
- Handoff requires a ready workspace, verified tenant email, an existing verified tenant user and a valid one-time provisioning token.
- Tenant login rejects unverified accounts.
- Verification, provisioning and handoff flows have dedicated regression coverage.
- Signup registration now normalizes the registration email/subdomain and performs central Tenant + Domain creation atomically inside the registration transaction.
- Signup client-flow coverage now includes successful registration, duplicate email/subdomain protection, supported-language/default-language inheritance, invalid input, JSON response contract, verification replay/expiry, provisioning-link expiry, resend throttling and verified handoff behavior.

### Authentication UI / Frontend Consistency

- A shared `public/css/velora-auth.css` layer consumes the official Velora brand tokens for authentication screens.
- Tenant Login and Super Admin Login use the same responsive auth shell, official Velora gradient, typography, theme handling and RTL/LTR behavior.
- Tenant Login and Super Admin Login use the Velora logo asset and no longer define independent legacy Indigo brand palettes.
- The email-verification completion screen uses the shared auth visual language and `logo-bais.png`.
- Find Account inherits the same Velora background, surfaces, typography, borders and primary-action styling through the shared auth layer without changing its existing JavaScript or routing behavior.
- Auth theme persistence uses the shared `velora-theme` storage key on the updated auth screens.
- Tenant Signup has a dedicated UI contract test covering the form structure, CSRF, POST contract, supported locales, direction metadata and password redisplay safety.
- Tenant Login uses the shared locale/message catalog rather than an Arabic-vs-English text branch for core copy.
- Tenant Login has one language dropdown, locale-aware RTL/LTR markup and a single-submit guard.
- Tenant Login API responses use localized auth messages instead of English-only hard-coded success/failure/throttle copy.
- Login localization has dedicated regression coverage across the supported locale registry.

### Translation Coverage

- All 15 supported locales have real translated `auth.php` and `booking.php` core bundles.
- All 15 supported locales have valid direction metadata (`rtl` for Arabic; `ltr` for the remaining supported locales).
- `messages.php`, `notifications.php`, `pagination.php`, `passwords.php` and `validation.php` coverage has been expanded across the supported locale set, with regression coverage for the core bundle/parity requirements currently enforced by the test suite.
- `tests/Feature/SupportedLocaleCoreCoverageTest.php` verifies core translation bundle existence, locale direction, and notification key/placeholder parity.
- Translation coverage is a milestone, not a claim that every Tenant-facing screen is fully translated.
- Remaining translation work includes feature-specific dashboard/admin copy, hard-coded Blade/JavaScript strings, emails and generated documents, plus quality review of runtime validation messages.

### Testing

- Feature tests cover booking, appointments, queue, billing, localization and other administration areas.
- Dedicated test base classes exist for tenant and super-admin scenarios.
- `TenantEmailVerificationGateTest` verifies the Admin security gate and explicit Signup language/default inheritance.
- `TenantVerificationLocaleTest` verifies tenant-language and explicit-language override behavior for the verification page using a real tenant database context.
- `TenantEmailVerificationTest` covers verification route/mail/tenant provisioning metadata.
- `SignupTenantHandoffTest` covers unverified access, verification and handoff behavior.
- `SignupClientFlowTest` covers end-to-end HTTP Signup client scenarios and security edge cases.
- `SignupUiContractTest` covers the Signup frontend contract across the supported locale registry.
- `TenantLoginLocaleUiTest` covers Tenant Login localization/UI invariants across the supported locale registry.
- `SupportedLocaleCoreCoverageTest` verifies core translation coverage and notification key/placeholder parity.
- Latest user-reported local full suite: **537 tests, 4311 assertions, 0 failures, 0 errors**.
- Latest user-reported focused Login UI suite: **5 tests, 333 assertions, 0 failures, 0 errors**.

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
9. Real email delivery must be validated outside the test suite with a working SMTP provider/sandbox.

### P2

10. Dashboard analytics should be refactored and optimized further.
11. Remaining hard-coded translations should be localized.
12. Browser/mobile/RTL visual QA should be completed, including the complete Tenant language lifecycle across all 15 supported UI locales.
13. Tenant Forgot Password / Reset Password flow still needs to be implemented and tested as a real Tenant-domain flow.
14. Documentation should be synchronized whenever a new implementation milestone is merged.

## Execution Rule

Every completed task should update this file with:

- Date.
- Scope.
- Files/areas changed.
- Tests run.
- Result.
- Any follow-up risk.

## Latest Implementation Entry

### 2026-08-29 — Signup E2E Hardening, Tenant Login Localization & Documentation Sync

Scope:
- Harden Signup from a real client perspective across frontend and backend.
- Preserve the intended security order: Tenant/Domain first, verification gate, Admin creation only after verification, then one-time handoff.
- Add Signup UI contract coverage.
- Make Tenant Login language behavior consistent with the 15-locale Tenant lifecycle.
- Localize Tenant Login API responses.
- Synchronize this implementation log with the actual repository state and latest user-reported tests.

Changed:
- `app/Services/TenantRegistrationService.php`
- `app/Http/Controllers/Auth/TenantRegistrationController.php`
- `app/Http/Controllers/Auth/TenantAuthController.php`
- `resources/views/auth/login.blade.php`
- `tests/Feature/SignupClientFlowTest.php`
- `tests/Feature/SignupUiContractTest.php`
- `tests/Feature/TenantLoginLocaleUiTest.php`
- `docs/SIGNUP_E2E_TEST_PLAN.md`
- `docs/TRANSLATION_COVERAGE.md`
- `docs/CHANGELOG_IMPLEMENTATION.md`

Behavior:
- Signup registration uses supported locale state and preserves the configured public default when language is omitted.
- Tenant + Domain registration is atomic and input is normalized before persistence.
- First Tenant Admin remains unavailable before email verification.
- Successful verification can return a 302 handoff redirect when the workspace is ready; tests assert the actual flow rather than forcing a 2xx response.
- Tenant Login uses the locale/message catalog for visible copy and API responses.
- Login language selection is presented as a single dropdown and persists through the existing Tenant language mechanism.

Validation:
- User-reported `SignupClientFlowTest`: **15 tests, 106 assertions, 0 failures, 0 errors**.
- User-reported `SignupTenantHandoffTest`: **8 tests, 31 assertions, 0 failures, 0 errors**.
- User-reported `TenantEmailVerificationGateTest`: **4 tests, 10 assertions, 0 failures, 0 errors**.
- User-reported `SignupUiContractTest`: **3 tests, 78 assertions, 0 failures, 0 errors**.
- User-reported `TenantLoginLocaleUiTest`: **5 tests, 333 assertions, 0 failures, 0 errors**.
- User-reported latest complete local suite: **537 tests, 4311 assertions, 0 failures, 0 errors**.
- SMTP/Mailtrap delivery remains an external environment validation item; the local queue previously demonstrated that the verification mail job can fail on external SMTP connectivity even when the queue itself is healthy.

Follow-up:
- Implement and test real Tenant Forgot Password / Reset Password.
- Perform browser/mobile/RTL visual QA for Signup → Verification → Login → Dashboard.
- Replace remaining hard-coded Tenant Dashboard/admin strings with feature namespaces.
- Add stronger Tenant isolation and object-level authorization tests.
- Validate real SMTP delivery with Mailtrap or another controlled sandbox before production.

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
- The targeted `TenantEmailVerificationGateTest` now passes: **4 tests, 10 assertions**.
- The latest full suite is recorded in the later 2026-08-29 implementation entry.
- Browser validation is still required for all supported languages, including RTL and non-Latin languages.

Follow-up:
- Continue the documented P0 tenant-isolation and object-level authorization audit.
- Complete browser/mobile visual QA.

### 2026-08-28 — Core Translation Coverage for Supported Locales

Scope:
- Ensure every supported platform locale has real core translation bundles.
- Add regression coverage for core bundle existence, locale direction and selected parity requirements.

Changed:
- `lang/*/auth.php`
- `lang/*/booking.php`
- `lang/*/messages.php`
- `lang/*/notifications.php`
- `lang/*/pagination.php`
- `lang/*/passwords.php`
- `lang/*/validation.php`
- `tests/Feature/SupportedLocaleCoreCoverageTest.php`
- `docs/TRANSLATION_COVERAGE.md`

Validation:
- User-reported core translation coverage test: **3 tests, 1126 assertions, 0 failures, 0 errors** after the expanded core checks.
- User-reported latest full suite after the expanded translation pass and later auth hardening is captured in the latest implementation entry.

Follow-up:
- Translate feature-specific Dashboard/admin copy.
- Audit hard-coded Blade/JavaScript strings, emails and generated documents.

### 2026-08-28 — Unified Velora Authentication UI

Scope:
- Establish one shared visual system for Tenant Login, Super Admin Login, Find Account and email-verification completion.
- Keep authentication/security behavior unchanged while removing divergent legacy visual implementations.
- Align the auth experience with the official Velora brand guidelines and the existing RTL/LTR localization model.

Changed:
- `public/css/velora-auth.css`
- `resources/views/auth/login.blade.php`
- `resources/views/super-admin/login.blade.php`
- `resources/views/landing/email-verified.blade.php`

Validation:
- A fresh local full-suite run after the UI commit was reported by the user: **509 tests, 2665 assertions, 0 failures, 0 errors**.
- SMTP/Mailtrap delivery remains an external environment validation item.
