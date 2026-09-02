# Velora Architecture Cleanup

## Scope

This cleanup removes application code that duplicated responsibilities already provided by Laravel or installed packages, while preserving domain-specific business rules and keeping the installed Composer package stack unchanged.

## Canonical sources

- **Locale routing and supported locales:** `niels-numbers/laravel-localizer` via `config/localizer.php`.
- **Roles and permissions:** `spatie/laravel-permission` remains the source of truth. `App\Models\Role` is retained as the application's compatibility model and extends Spatie's model without reimplementing RBAC.
- **Multi-tenancy:** `stancl/tenancy` is the source for domain tenancy initialization.
- **API token abilities:** Laravel Sanctum's `CheckAbilities` middleware.
- **HTTP rate limiting:** Laravel's native named `RateLimiter`.
- **Payment, PDF, Excel, QR, HTTP, and API-auth integrations:** existing installed packages remain in place; their application services are adapters/domain logic, not package replacements.

## Removed duplicate implementations

### Native Laravel rate limiting

Removed `app/Http/Middleware/ThrottleRequests.php` and its alias. Public booking now uses `throttle:public-booking`, defined with Laravel's `RateLimiter`.

### Sanctum token abilities

Removed `app/Http/Middleware/CheckTokenAbility.php` and mapped the existing `ability` alias to Sanctum's built-in `CheckAbilities` middleware.

### Stancl tenancy initialization

Removed `app/Http/Middleware/InitializeTenancyByDomain.php`. The application uses `Stancl\Tenancy\Middleware\InitializeTenancyByDomain` directly.

### Central locale duplication

Removed `SetCentralLocale`. `EnforceCentralLocale` is now the central-domain policy layer, while `config/localizer.php` owns the supported/default locale configuration. `config/locales.php` is UI metadata only.

### Runtime public translation injection

Removed the three public translation-injection middlewares and the unused legacy `_landing_translations.php` registry. Public copy now comes from the normal Laravel locale files and JSON language files. `InjectVeloraBrandStyles` remains only because it still provides non-translation presentation/compatibility behavior; its workspace resolver copy now comes from `landing.workspace_finder.{locale}` instead of hard-coded English strings.

### Role decision duplication

`CheckRole` remains only for the platform-specific unauthorized response behavior. The actual role test now delegates to Spatie Permission with `hasAnyRole()` instead of inspecting only the first role manually.

## Intentionally retained

- Tenant-specific locale selection and token binding.
- Subscription enforcement, maintenance mode, geo/country detection, onboarding redirects, and Super Admin authorization because these encode application rules.
- `App\Models\Role` because configuration, seeders, infrastructure, and tests still reference the application's namespace.
- Custom model translation support and legacy `_ar` / `*_i18n` columns because the replacement package `spatie/laravel-translatable` is not installed and tenant data has not been migrated. These require a dedicated data migration before removal.
- Historical migrations and compatibility schema are retained; deployed migration history must not be deleted simply for source cleanup.

## Package policy

No Composer package was removed or replaced. The installed package set remains unchanged.
