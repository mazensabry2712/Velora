# Architecture Guards

Velora is being migrated as a modular monolith with explicit boundaries.

## Dependency direction

```text
HTTP / Console
      ↓
Application
      ↓
Domain
      ↓
Infrastructure
```

### Rules

1. Domain and Application code must not depend on `App\Http` or `Illuminate\Http`.
2. Domain code must not depend on Blade/View APIs.
3. Domain code must not depend on concrete payment providers such as Stripe, Moyasar, Paymob, or Fawry.
4. Controllers should orchestrate HTTP concerns only; business workflows belong to Application Actions/Use Cases.
5. Infrastructure implementations are wired through contracts where a business-facing abstraction is required.
6. Tenant-bound operations must execute only after tenant context has been established and must never trust a tenant identifier supplied solely by a client payload.
7. Payment activation/refund state changes must originate from verified provider processing, not browser return URLs alone.

The architecture tests in `tests/Unit/Architecture/LayerBoundaryTest.php` provide automated protection for the first three rules. Additional guards should be added as legacy services are retired.
