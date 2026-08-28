# Velora — Tenant Translation Coverage

## Supported locales

The Tenant/Public locale registry currently supports:

`ar`, `en`, `fr`, `es`, `de`, `it`, `pt`, `ru`, `zh`, `ja`, `tr`, `hi`, `ko`, `nl`, `id`.

## Completed in this pass

Every supported locale now has real translated core bundles for:

- `auth.php`
- `booking.php`

The coverage regression test `tests/Feature/SupportedLocaleCoreCoverageTest.php` verifies that every configured locale has both bundles and a valid `ltr`/`rtl` direction.

## Language lifecycle

The currently selected public default is `ar`, but the application reads the central `public_default_locale` setting when resolving the public default. A future Super Admin control can change that value without rewriting existing Tenant defaults.

A Signup language becomes the Tenant default. An authenticated Tenant user's persisted `users.locale` overrides the Tenant default and survives logout/login until the user changes it again.

Super Admin localization remains outside this Tenant language system for now.

## Remaining work

Core bundle coverage is not the same as complete application translation. The remaining Tenant-facing bundles and hard-coded view strings still require a full translation pass, especially:

- `messages.php`
- `notifications.php`
- `pagination.php`
- `passwords.php`
- `validation.php`
- dashboard/admin feature-specific strings
- emails and generated documents where user-facing text is still hard-coded

No fallback-to-English behavior should be counted as a completed translation.

## Validation

Run after pulling the latest changes:

```powershell
php artisan optimize:clear
php artisan test --parallel --processes=12 tests/Feature/SupportedLocaleCoreCoverageTest.php
php artisan test --parallel --processes=12
```
