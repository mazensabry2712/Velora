# QA Finding — Billing Portal Central Connection

**Area:** Billing / Stripe customer portal

**Finding:** `BillingController::portal()` used a hard-coded `DB::connection('mysql')` while the rest of the current billing/tenancy contract resolves the central database from `tenancy.database.central_connection`.

**Why this matters:** The hard-coded connection could bypass the configured central connection contract in environments that intentionally use a different central connection name. It also made the portal implementation inconsistent with the checkout path and the hardened billing services.

**Fix:** The method now resolves:

```php
$centralConn = config('tenancy.database.central_connection', 'mysql');
```

and queries the subscription through `DB::connection($centralConn)`.

**Regression:** `tests/Unit/BillingCentralConnectionContractTest.php` guards the controller contract against reintroducing a hard-coded `DB::connection('mysql')` inside `portal()`.

**Status:** Fixed on `main`. Fresh MySQL CI evidence is still required before release certification.
