# Velora Tenant Password Recovery

## Scope

Password recovery is tenant-scoped and is intentionally separate from Super Admin authentication.

## Flow

1. `GET /forgot-password` renders the recovery form.
2. `POST /forgot-password` validates the email, rate-limits the request, and never discloses whether the account exists.
3. Verified tenant users receive a queued `TenantPasswordResetMail` containing a single-use 64-character token.
4. The token is stored as a SHA-256 digest in the tenant database with the user's locale and a 60-minute TTL.
5. `GET /reset-password/{token}?email=...` validates the digest and expiry, then renders the reset form in the locale captured for that user.
6. `POST /reset-password/{token}` validates the token again, hashes the new password through the User model, revokes existing Sanctum tokens, deletes the reset token, restores the user's locale, and redirects to tenant login.

## Security

- Reset tokens never persist in plaintext.
- Unknown and unverified emails return the same generic success response.
- Reset requests are limited to 3 attempts per tenant/IP/email window.
- Reset tokens expire after 60 minutes.
- Reset tokens are single-use.
- Token records live in each tenant database, preventing cross-tenant reuse.
- Successful reset revokes existing personal access tokens.
- Super Admin is outside this flow.

## Localization

The recovery UI and reset email use the `password_reset` translation bundle. All 15 supported tenant locales must provide the same key set.

The user's persisted locale is stored alongside the reset token so the reset page and email keep the user's selected language even when it differs from the tenant default.

## Migration

Run tenant migrations after pulling this change:

```powershell
php artisan tenants:migrate --force
```

## Verification

Run:

```powershell
php artisan test --parallel --processes=12 tests/Feature/TenantPasswordResetContractTest.php
php artisan test --parallel --processes=12 tests/Feature/TenantPasswordResetFlowTest.php
php artisan test --parallel --processes=12
```

The integration suite covers successful reset, old-password rejection, token single-use, generic unknown-email behavior, and cross-tenant token isolation.
