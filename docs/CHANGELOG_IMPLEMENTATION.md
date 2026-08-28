# Velora — Implementation History & Work Log

## Purpose

This file is the living record of the important work already performed and the engineering work that should be performed next.

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
- The public/tenant locale registry contains the same supported language set used by the Landing: `ar`, `en`, `fr`, `es`, `de`, `it`, `pt`, `ru`, `zh`, `ja`, `tr`, `hi`, `ko`, `nl`, `id`.
- Arabic is currently the public/default fallback, but the public default is now read from the central `SystemSetting` key `public_default_locale`, so a future Super Admin language selector can change the public default without hard-coding `ar`.
- Explicit locale routes, persisted public session/cookie state and the configurable public default continue to drive the central public experience.
- Tenant email-verification pages resolve locale from the persisted tenant language.
- Arabic verification rendering supports RTL and the Velora-branded verification page.

### Tenant Language Lifecycle

- The language selected during Signup is persisted as the new Tenant default language.
- If Signup does not explicitly provide a language, the Tenant inherits the current central public default language rather than a hard-coded English default.
- Before a user is authenticated, tenant Provisioning/Verification resolves from the Tenant default language.
- Tenant users now have a persistent `locale` preference stored in the tenant `users` table.
- After authentication, persisted `User.locale` takes precedence over session and Tenant default locale.
- Changing language from a tenant-domain language control updates the authenticated User's persisted locale and the current session.
- The persisted User locale survives logout/login and does not silently revert to the Tenant default.
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

### Authentication UI / Frontend Consistency

- A shared `public/css/velora-auth.css` layer now consumes the official Velora brand tokens for authentication screens.
- Tenant Login and Super Admin Login use the same responsive auth shell, official Velora gradient, typography, theme handling and RTL/LTR behavior.
- Tenant Login and Super Admin Login use the Velora logo asset and no longer define independent legacy Indigo brand palettes.
- The email-verification completion screen uses the shared auth visual language and `logo-bais.png`.
- Find Account inherits the same Velora background, surfaces, typography, borders and primary-action styling through the shared auth layer without changing its existing JavaScript or routing behavior.
- Auth theme persistence uses the shared `velora-theme` storage key on the updated auth screens.

### Testing

- Feature tests cover booking, appointments, queue, billing, localization and other administration areas.
- Dedicated test base classes exist for tenant and super-admin scenarios.
- `TenantEmailVerificationGateTest` verifies that the first Tenant Admin does not exist before email verification and is created only after successful verification.
- `TenantVerificationLocaleTest` verifies tenant-language and explicit-language override behavior for the verification page using a real tenant database context.
- `TenantEmailVerificationTest` covers verification route/mail/tenant provisioning metadata.
- `SignupTenantHandoffTest` covers unverified access, verification and handoff behavior.
- Latest user-reported local full suite before the current tenant-user locale changes: **509 tests, 2665 assertions, 0 failures, 0 errors**.

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

### P2

9. Dashboard analytics should be refactored and optimized further.
10. Remaining hard-coded translations should be localized.
11. Production operations/documentation should be completed.
12. Browser/mobile/RTL visual QA should be completed, including the updated authentication surfaces and the complete tenant language lifecycle.

## Execution Rule

Every completed task should update this file with:

- Date.
- Scope.
- Files/areas changed.
- Tests run.
- Result.
- Any follow-up risk.

## Latest Implementation Entry

### 2026-08-28 — Tenant Language Persistence & Configurable Public Default

Scope:
- Make the Landing's current default language configurable rather than permanently hard-coded to Arabic.
- Use the current public default when a Signup language is not explicitly selected.
- Persist Tenant user language choices so a user's manual language change survives logout/login.
- Ensure authenticated User locale overrides session and Tenant default locale.
- Keep Super Admin localization outside the Tenant language system for now.

Changed:
- `app/Http/Middleware/LocaleSignalDetector.php`
- `app/Http/Middleware/SetTenantLocale.php`
- `app/Http/Controllers/Auth/TenantAuthController.php`
- `app/Models/User.php`
- `database/migrations/tenant/2026_08_28_000001_add_locale_to_users_table.php`
- `app/Services/TenantRegistrationService.php`
- `routes/tenant.php`
- `docs/CHANGELOG_IMPLEMENTATION.md`

Behavior:
- Current public default fallback is `ar`.
- Future Super Admin setting key: `public_default_locale`.
- Signup language, when supplied and supported, becomes the Tenant default.
- Signup without a language inherits `public_default_locale`.
- Tenant user locale precedence is: persisted User locale → explicit request/session override → Tenant default → application fallback.
- Authenticated tenant language changes update both the User's persisted locale and the active session.
- Existing Tenants are unaffected by future changes to `public_default_locale`.

Validation:
- The implementation was committed to `main` but has not yet been run through the local PHPUnit suite after these new changes.
- Run the full suite after pulling the commits.
- Browser validation is still required for every supported language, especially RTL and non-Latin languages.

Follow-up:
- Add/expand automated regression coverage for Tenant user locale persistence across login/logout.
- Add the future Super Admin `Public Default Language` control against `SystemSetting::get/set('public_default_locale', ...)`.
- Continue the documented P0 tenant-isolation and object-level authorization audit.

## Previous Implementation Entry

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

Related behavior retained:
- Tenant authentication and verification gate remain unchanged.
- Super Admin login continues to use its existing POST route and authorization behavior.
- Find Account logic, availability checking and redirects remain unchanged.

Validation:
- A fresh local full-suite run after this UI commit was reported by the user: **509 tests, 2665 assertions, 0 failures, 0 errors**.
- SMTP/Mailtrap delivery remains an external environment validation item and is not represented as a passing automated test.

Follow-up:
- Perform browser visual QA for light/dark, Arabic RTL, English LTR and mobile breakpoints across Signup, Provisioning, Verification, Find Account, Tenant Login and Super Admin Login.
- Continue with the documented P0 tenant-isolation and object-level authorization audit.
- Keep billing webhook/idempotency, storage quota, monitoring, backup/restore and release-readiness items open until verified.
