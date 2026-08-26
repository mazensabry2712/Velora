# Velora — Test Commands

## 1. First-time setup

```bash
composer install
npm ci
copy .env.example .env
php artisan key:generate
```

For PowerShell on Windows, use:

```powershell
Copy-Item .env.example .env
```

## 2. Run the complete backend suite

```bash
php artisan test --compact
```

## 3. Run with a readable PHPUnit report

```bash
php artisan test
```

## 4. Run only unit tests

```bash
php artisan test tests/Unit
```

## 5. Run only feature tests

```bash
php artisan test tests/Feature
```

## 6. Run security tests

```bash
php artisan test --group=security
```

## 7. Run smoke tests

```bash
php artisan test --group=smoke
```

## 8. Run booking tests

```bash
php artisan test tests/Feature/PublicBookingTest.php
php artisan test tests/Feature/AppointmentActionsTest.php
php artisan test tests/Feature/AppointmentQueueIntegrationTest.php
php artisan test tests/Feature/Requests/AppointmentRequestTest.php
```

## 9. Run queue tests

```bash
php artisan test tests/Feature/PublicQueueTest.php
php artisan test tests/Feature/Admin/QueueControllerTest.php
php artisan test tests/Feature/AppointmentQueueIntegrationTest.php
```

## 10. Run admin tests

```bash
php artisan test tests/Feature/Admin
```

## 11. Run billing tests

```bash
php artisan test tests/Feature/Billing
```

## 12. Run Super Admin tests

```bash
php artisan test tests/Feature/SuperAdmin
```

## 13. Run multi-region / localization tests

```bash
php artisan test tests/Feature/MultiRegion
php artisan test tests/Feature/LocaleSwitchTest.php
```

## 14. Run tenant-security tests directly

```bash
php artisan test tests/Feature/Security/AuthorizationMatrixTest.php
php artisan test tests/Feature/Security/TenantIsolationTest.php
```

## 15. Run application smoke tests

```bash
php artisan test tests/Feature/Health/ApplicationSmokeTest.php
```

## 16. Check formatting

```bash
vendor/bin/pint --test
```

To automatically format code locally:

```bash
vendor/bin/pint
```

## 17. Build frontend assets

```bash
npm run build
```

## 18. Run everything before release

```bash
composer validate --strict
vendor/bin/pint --test
php artisan test --compact
npm ci
npm run build
```

## 19. Recommended debugging order

When the full suite fails, do not immediately rerun everything.

1. Run the failed test file alone.
2. Run the related module directory.
3. Run the relevant security test if the failure involves permissions or tenant data.
4. Run the complete suite.
5. Run the frontend build.

Example:

```bash
php artisan test tests/Feature/Security/TenantIsolationTest.php
php artisan test tests/Feature/Security
php artisan test --compact
npm run build
```

## 20. Release decision

A release candidate is considered test-clean only when:

- the complete PHPUnit suite passes;
- security and tenancy tests pass;
- Stripe webhook tests pass;
- `vendor/bin/pint --test` passes;
- the frontend build passes;
- database migrations succeed on a clean environment;
- CI is green on `main`.
