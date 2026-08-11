# Velora — Admin Subscription & Settings Restructure

This zip contains the final versions of every file changed for the
`/admin/subscription` and `/admin/settings` restructuring. The folder
structure mirrors the repo root — copy each file to the same path in
your local project (overwriting the existing one), then add the new
partial files.

## How to apply
1. Copy every file below into your Velora repo at the matching path
   (overwrite existing files).
2. **Delete** `resources/views/billing/index.blade.php` — it's no
   longer used (replaced by `resources/views/admin/subscription/index.blade.php`).
3. Run `git status` / `git diff` to review, then commit.

## Files included

### Subscription page (`/admin/subscription`)
- `app/Http/Controllers/BillingController.php` — removed the duplicate
  `index()` dashboard method; kept payment-gateway logic only
  (checkout, portal, Moyasar, trial extension).
- `app/Http/Controllers/Web/SubscriptionController.php` — now the
  single controller for the dashboard, pulling invoices too.
- `app/Services/SubscriptionService.php` — added `stripe_customer_id`,
  `trial_extended`, `grace_ends_at`, `stripe_price_id`, and a new
  `getInvoices()` method.
- `resources/views/admin/subscription/index.blade.php` — unified
  dashboard (usage bars, billing portal button, trial extend, instant
  Stripe checkout, invoices).
- `resources/views/admin/subscription/upgrade.blade.php` — added
  instant checkout alongside the existing sales-assisted request flow.
- `routes/tenant.php` — `/admin/subscription` now points at
  `SubscriptionController::index` instead of the removed
  `BillingController::index`.

### Settings page (`/admin/settings`)
- `resources/views/admin/settings/index.blade.php` — rebuilt. Fixes:
  unclosed `@section('content')`, a stray `</main>` with no matching
  `<main>`, two unclosed `<div>` tags, and a `<script>` block that was
  never wrapped in `@push('scripts')`. These were real bugs, not just
  style issues.
- `resources/views/admin/settings/partials/*.blade.php` (new) — the
  336-line file split into `business-info`, `languages`, `logo`, and
  `social-media` partials.
- Added the previously-missing `business_name_ar` field to the form
  (it was already validated and stored, just never rendered).

See `CHANGED_FILES.txt` for the raw file list.
