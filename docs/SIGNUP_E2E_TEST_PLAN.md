# Velora — Signup End-to-End Acceptance Matrix

## Scope

This document defines the current production-oriented Signup journey for a new Tenant customer, covering frontend, backend, localization, provisioning, email verification, security gates and future extensibility.

## Customer Journey

1. Open the localized `/signup` page.
2. Complete business, subdomain, email, password, country, language and Terms fields.
3. Submit Signup through the web form or JSON contract.
4. Create the central Tenant and its Tenant Domain.
5. Persist the selected Tenant default language.
6. Create provisioning and email-verification credentials.
7. Queue the verification email.
8. Redirect the customer to the provisioning page.
9. Keep the Tenant Admin absent until email verification succeeds.
10. Verify the email with the single-use, expiring verification token.
11. Create/activate the first Tenant Admin with the Tenant default locale.
12. Allow handoff only when the workspace is ready and the email is verified.
13. Persist a user's later language override independently of the Tenant default.

## Acceptance Scenarios

### Frontend

- Default Signup renders successfully.
- Non-default localized Signup renders with the requested locale and correct text direction.
- Every supported platform locale can render its localized Signup route.
- The form exposes business name, business type, subdomain, email, password, password confirmation, country, Terms and language controls.
- The form uses POST and includes CSRF protection.
- Required browser-side fields are present.
- Password confirmation is enforced server-side.
- Signup returns validation state without leaking submitted password values into the rendered page/session input.
- The UI contract is covered independently from the business-flow tests.

### Backend

- Valid Signup creates exactly one central Tenant and one Tenant Domain.
- Invalid subdomains are rejected before Tenant creation.
- Duplicate subdomains are rejected.
- Duplicate email registration is rejected.
- Unsupported locales are rejected from the configurable locale registry.
- Signup without an explicit locale inherits `public_default_locale`.
- Explicit Signup locale becomes the Tenant default.
- All configured platform locales are accepted by Signup.
- JSON Signup returns a stable machine-readable provisioning contract.
- Verification email is queued after successful registration.

### Security

- The first Tenant Admin is not created before email verification.
- Invalid verification tokens return 404.
- Expired verification tokens return 404.
- Verification tokens are single-use.
- Expired provisioning links are rejected.
- Verification resend is rate-limited.
- Resend after verification is idempotent and reports the verified state.
- Handoff remains blocked unless the Tenant is ready and the User is verified.

### Localization

- Signup locale is persisted on the Tenant.
- The first verified Tenant Admin receives the Tenant locale.
- Tenant language remains separate from Super Admin localization.
- The language validator reads the central supported-locale registry rather than a duplicated hard-coded list.
- Signup UI direction is driven by the centralized `localizer.locale_directions` registry.

## Automated Coverage

`tests/Feature/SignupClientFlowTest.php` covers the customer journey and business/security acceptance matrix.

`tests/Feature/SignupUiContractTest.php` covers the rendered Signup UI contract across every supported locale, including form fields, POST method, CSRF presence, locale direction and password non-disclosure on validation redisplay.

Related existing suites:

- `tests/Feature/SignupTenantHandoffTest.php`
- `tests/Feature/TenantEmailVerificationTest.php`
- `tests/Feature/TenantEmailVerificationGateTest.php`
- `tests/Feature/TenantVerificationLocaleTest.php`
- `tests/Feature/SupportedLocaleCoreCoverageTest.php`

## Local Validation

After pulling the latest `main`:

```powershell
php artisan optimize:clear
php artisan test --parallel --processes=12 tests/Feature/SignupUiContractTest.php
php artisan test --parallel --processes=12 tests/Feature/SignupClientFlowTest.php
php artisan test --parallel --processes=12 tests/Feature/SignupTenantHandoffTest.php
php artisan test --parallel --processes=12 tests/Feature/TenantEmailVerificationGateTest.php
php artisan test --parallel --processes=12 tests/Feature/TenantEmailVerificationTest.php
php artisan test --parallel --processes=12 tests/Feature/TenantVerificationLocaleTest.php
php artisan test --parallel --processes=12 tests/Feature/SupportedLocaleCoreCoverageTest.php
php artisan test --parallel --processes=12
```

The SignupClientFlowTest and SignupUiContractTest are intentionally separate so future features such as CAPTCHA, OAuth/SSO, billing selection, invite flows, richer client-side validation or additional anti-abuse controls can be added without weakening the current acceptance contract.
