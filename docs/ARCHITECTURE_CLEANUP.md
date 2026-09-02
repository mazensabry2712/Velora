# Velora Architecture Cleanup

## Scope

This cleanup removes application code that duplicated responsibilities already provided by Laravel or installed packages, while preserving domain-specific business rules and keeping the installed package stack unchanged.

## Canonical sources

- **Locale routing and supported locales:** `niels-numbers/laravel-localizer` via `config/localizer.php`.
- **Roles and permissions:** `spatie/laravel-permission` remains the source of truth. `App\Models\Role` is retained only as a compatibility model wrapper because the application still references that namespace.
- **Multi-tenancy:** `stancl/tenancy` remains responsible for tenancy initialization. Tenant-specific middleware is application behavior and remains.
- **API token abilities:** Laravel Sanctum is now used directly through `Laravel\Sanctum\Http\Middleware\CheckAbilities`.
- **HTTP rate limiting:** Laravel's native named `RateLimiter` is now used for public booking.
- **Payment, PDF, Excel, QR, HTTP, and API-auth integrations:** existing installed packages remain in place; their surrounding application services are domain adapters, not replacement packages.

## Removed duplicate implementations

### Native Laravel rate limiting

Removed `app/Http/Middleware/ThrottleRequests.php`, removed its middleware alias, and replaced `throttle.api:public-booking,5,1` with the native named limiter `throttle:public-booking`.

The limiter preserves tenant + client-IP isolation and the JSON 429 response behavior.

### Sanctum token ability middleware

Removed `app/Http/Middleware/CheckTokenAbility.php` and registered Sanctum's built-in `CheckAbilities` middleware under the existing `ability` alias.

### Central locale registry duplication

`config/locales.php` no longer owns `default` or `supported` locale decisions. It is now UI metadata only. Locale resolution uses `config/localizer.php`.

### Central locale middleware duplication

`SetCentralLocale` was removed. `EnforceCentralLocale` now handles both public central-domain locale resolution and the Super Admin's persisted `central_locale` preference.

## Intentionally retained custom code

Custom middleware such as tenant locale selection, tenant-token binding, subscription enforcement, maintenance mode, geo/country detection, onboarding redirects, and Super Admin authorization encode product rules or tenancy behavior that are not direct replacements for an installed package.

The custom translation coverage middleware is intentionally not removed in this pass because several supported locales still inherit newer public signup copy from English locale files. Removing it before migrating that copy into the locale files would reintroduce English leakage. Its eventual removal is a translation-data migration task, not a safe deletion-only cleanup.

## Package policy

No package was removed or replaced. The Composer dependency set remains unchanged.
