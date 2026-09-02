# Velora Architecture Cleanup

## Scope

This cleanup removes application code that duplicated responsibilities already provided by Laravel or installed packages, while preserving domain-specific business rules.

## Canonical sources

- **Locale routing and supported locales:** `niels-numbers/laravel-localizer` via `config/localizer.php`.
- **Roles and permissions:** `spatie/laravel-permission` is the source of truth. Application code now uses Spatie's Role model directly.
- **Multi-tenancy:** `stancl/tenancy` is the source for tenancy initialization and tenant lifecycle jobs.
- **API token abilities:** Laravel Sanctum's `CheckAbilities` middleware.
- **HTTP rate limiting:** Laravel's native named `RateLimiter`.
- **Staff identity and scheduling:** the dedicated `Staff` entity and `staff_working_hours` table are canonical.
- **Payment, PDF, Excel, QR, HTTP, and API-auth integrations:** existing installed packages remain in place; application services are adapters/domain logic, not competing implementations.

## Removed duplicate implementations

### Native Laravel rate limiting

Removed the custom request-throttling middleware. Public booking uses Laravel's named `RateLimiter`.

### Sanctum token abilities

Removed the custom token-ability middleware. The `ability` alias points to Sanctum's `CheckAbilities` middleware.

### Stancl tenancy initialization

Removed the application duplicate of domain-tenancy initialization. The app uses Stancl's middleware directly.

### Central locale duplication

Removed obsolete central-locale enforcement/injection layers. Supported locales are configured centrally and tenant-specific locale selection remains an application rule.

### Runtime public translation injection

Removed obsolete public translation-injection middlewares and the unused landing translation registry. Public copy comes from normal Laravel locale files and JSON language files.

### Role decision duplication

`CheckRole` is the only remaining application role middleware. Actual authorization delegates to Spatie Permission with `hasAnyRole()`.

### Thin infrastructure adapters

Removed redundant adapters that only delegated to application services:

- `LegacyReportReader` → `ReportService` implements `ReportReader` directly.
- `LegacyCountryPriceSelector` → `PricingService` implements `CountryPriceSelector` directly.
- `LegacyTenantRegistrar` → `TenantRegistrationService` is bound directly to `TenantRegistrar`.
- `Services\PaymentGatewayRouter` class alias → infrastructure payment router is the only gateway-router implementation.

### Staff schedule duplication

The old `StaffSchedule` / `staff_schedules` system duplicated the newer dedicated staff availability model. The canonical schedule is now `StaffWorkingHours` / `staff_working_hours`, keyed by `staff_id`.

The application model relationships keep the public `schedules` and `activeSchedules` names but resolve them through the user's `Staff` profile to the canonical working-hours records. A forward migration copies legacy schedule rows into canonical rows before removing the old table. The old `StaffSchedule` model has been removed from application code.

## Intentionally retained

- Tenant-specific locale selection and token binding.
- Subscription enforcement, maintenance mode, geo/country detection, onboarding redirects, and Super Admin authorization because these encode application rules.
- Custom model translation support and legacy `_ar` / `*_i18n` columns until `spatie/laravel-translatable` is installed and tenant data is migrated.
- Custom activity logging until `spatie/laravel-activitylog` is installed and existing audit data is migrated.
- The custom notification-delivery ledger because it tracks channel-specific delivery/recovery state and is used by multiple notification flows.
- Historical migrations and compatibility schema. Deployed migration history is not deleted for source cleanup.

## Remaining planned migrations

1. Migrate custom model translations to `spatie/laravel-translatable` with a data migration.
2. Migrate `ActivityLog` to `spatie/laravel-activitylog` with audit-data preservation.
3. Migrate the legacy FCM transport to a maintained Laravel notification channel.
4. Finish the staff-service pivot migration from `user_id` to canonical `staff_id` after all production/test consumers are converted.
5. Reconcile remaining `customer_id` / `customer_id_new` and other legacy booking columns through a data-safe schema migration.

## Package policy

No Composer package is removed merely for cleanup. New packages are introduced only when they replace a real custom subsystem and can be installed with a consistent `composer.lock`, schema/data migration, and regression coverage.
