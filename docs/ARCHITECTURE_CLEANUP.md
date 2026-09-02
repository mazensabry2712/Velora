# Velora Architecture Cleanup

## Scope

This cleanup removes application code that duplicated responsibilities already provided by Laravel or installed packages, while preserving domain-specific business rules and keeping the installed package stack unchanged.

## Canonical sources

- **Locale routing and supported locales:** `niels-numbers/laravel-localizer` via `config/localizer.php`.
- **Roles and permissions:** `spatie/laravel-permission` remains the source of truth. `App\Models\Role` is retained because the application, configuration, seeders, and tests still reference that namespace; the class extends Spatie's model without reimplementing RBAC.
- **Multi-tenancy:** `stancl/tenancy` remains responsible for tenancy initialization. Tenant-specific middleware is application behavior and remains.
- **API token abilities:** Laravel Sanctum is used directly through `Laravel\Sanctum\Http\Middleware\CheckAbilities`.
- **HTTP rate limiting:** Laravel's native named `RateLimiter` is used for public booking.
- **Payment, PDF, Excel, QR, HTTP, and API-auth integrations:** existing installed packages remain in place; their surrounding application services are domain adapters, not replacement packages.

## Removed duplicate implementations

### Native Laravel rate limiting

Removed `app/Http/Middleware/ThrottleRequests.php` and its alias. Public booking now uses the native named limiter `throttle:public-booking`.

The limiter preserves tenant + client-IP isolation and the JSON 429 response behavior.

### Sanctum token ability middleware

Removed `app/Http/Middleware/CheckTokenAbility.php` and mapped the existing `ability` alias to Sanctum's built-in `CheckAbilities` middleware.

### Central locale registry duplication

`config/locales.php` no longer owns `default` or `supported` locale decisions. It is UI metadata only. Locale resolution uses `config/localizer.php`.

### Central locale middleware duplication

`SetCentralLocale` was removed. `EnforceCentralLocale` now handles central public locale resolution and the Super Admin persisted locale preference.

### Runtime public translation injection

Removed the public auth/login translation injection middleware. The supported locale files already contain the corresponding direct-string and `landing.*` translations, so Laravel's normal language loader is now the only source for this public copy.

## Intentionally retained custom code

Custom middleware such as tenant locale selection, tenant-token binding, subscription enforcement, maintenance mode, geo/country detection, onboarding redirects, and Super Admin authorization encode product rules or compatibility behavior that are not direct replacements for an installed package.

`CheckRole` remains custom only for its platform-specific redirect/JSON authorization responses; the actual role decision delegates to Spatie Permission via `hasAnyRole()`.

The model translation trait and legacy `_ar`/`*_i18n` fields were not deleted because `spatie/laravel-translatable` is not installed. Removing them safely requires installing the replacement package, migrating existing tenant data, and updating all read/write paths first.

`InjectVeloraBrandStyles` remains because it currently performs non-translation presentation behavior (branding CSS, language-switcher injection, and legacy UI compatibility). Its hard-coded user-facing copy is a separate refactor target; deleting the middleware outright would remove unrelated presentation behavior.

## Package policy

No Composer package was removed. The installed dependency set remains unchanged.
