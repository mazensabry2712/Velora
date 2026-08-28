# Velora — Subscription Lifecycle & Data Retention Policy

## 1. Purpose

This document defines the mandatory lifecycle for every Velora tenant after signup. The lifecycle is a platform-level contract and must be implemented consistently across Backend, Frontend, Billing, Authorization, Notifications, Scheduled Jobs, Storage, and Tests.

## 2. Canonical 27-Day Lifecycle

```text
DAY 0       Signup
DAY 0-7     Full Trial
DAY 7-21    Read-Only (14 days)
DAY 21-27   Locked / Payment Required (6 days)
DAY 27      Permanent Deletion
```

Total retention window: **27 days from the deterministic lifecycle anchor**.

## 3. Canonical States

```text
trial
read_only
locked
active
cancelled
deleted
```

`trial` and `active` provide normal authorized workspace access.

`read_only` allows existing data to be read but denies business mutations server-side.

`locked` hides normal workspace data and denies normal workspace reads/writes. Billing, payment, support and logout remain available.

`deleted` is terminal.

Legacy billing states such as `grace` or `expired` must not create an alternate trial-retention timeline; any migration path must resolve them into the canonical lifecycle.

## 4. Deterministic Date Contract

Use one lifecycle anchor, preferably `subscription.started_at` for a new trial.

```text
trial_ends_at      = anchor + 7 days
read_only_ends_at  = anchor + 21 days
locked_at           = anchor + 21 days
delection_at       = anchor + 27 days
```

Invariant:

```text
started_at < trial_ends_at
trial_ends_at < read_only_ends_at
read_only_ends_at = locked_at
locked_at < deletion_at
```

Do not derive lifecycle boundaries from unrelated request, browser, or provider timestamps.

## 5. Trial — Day 0 to Day 7

Full authorized access is available, subject to normal permissions and plan limits.

The UI should show remaining trial time and an upgrade CTA.

## 6. Read-Only — Day 7 to Day 21

All existing tenant data remains visible. Business writes are rejected by the server.

Allowed:

```text
Read workspace/data
Subscription
Billing
Checkout/payment
Support
Logout
```

Denied:

```text
Create/update/delete business data
Queue mutations
Operational settings mutations
Other business writes
```

Suggested API contract:

```json
{
  "success": false,
  "error": "SUBSCRIPTION_READ_ONLY",
  "message": "Your workspace is currently read-only. Upgrade to restore full access.",
  "upgrade_url": "/admin/subscription/upgrade"
}
```

Frontend hiding or disabling buttons is UX only; server authorization remains mandatory.

## 7. Locked — Day 21 to Day 27

Normal workspace data is hidden behind a payment wall. Direct URL/API access must not bypass the lock.

Allowed exceptions:

```text
Subscription
Billing
Checkout/payment
Payment verification/callback
Support/contact
Logout
```

Suggested JSON error:

```json
{
  "success": false,
  "error": "SUBSCRIPTION_LOCKED",
  "message": "Your Velora workspace is locked. Please upgrade to restore access.",
  "upgrade_url": "/admin/subscription/upgrade",
  "deletion_at": "YYYY-MM-DDTHH:MM:SSZ"
}
```

## 8. Payment and Restoration

Payment state must be controlled by verified provider events/webhooks, not by browser success redirects alone.

A verified payment before deletion must:

```text
set subscription = active
restore full access
preserve tenant identity
preserve tenant domain
preserve users
preserve all existing tenant data
cancel pending deletion scheduling
```

The tenant must not be rebuilt during restoration.

## 9. Promo Codes

Promo codes belong to Billing/Checkout, not Signup.

```text
Signup page/request -> no promo-code processing
Checkout/payment    -> promo code may be validated/applied
```

## 10. Permanent Deletion — Day 27

At the deletion deadline, the tenant and all tenant-owned application resources are permanently deleted.

The deletion scope must follow the actual data model and include, as applicable:

```text
Tenant record
tenant domains
tenant users/subscriptions
customers
staff
services
appointments
queues
reports/data
settings
invoices
tenant-owned notifications/audit data
uploaded media
avatars
documents
attachments
exports
generated files
tenant-specific cached/file artifacts
isolated tenant database
```

Database deletion alone is not sufficient.

## 11. Storage Cleanup

Every tenant-owned filesystem object must be removed from all application-controlled storage locations, including tenant directories, uploads, avatars, documents, attachments, exports and generated files.

A successful deletion must leave no intentionally retained tenant-owned application storage.

## 12. Scheduled Deletion and Idempotency

Permanent deletion must be performed by scheduled/background processing and never in a user-facing HTTP request.

```text
Scheduler
  -> find deletion_at <= now
  -> clean files/storage
  -> remove tenant database/data
  -> remove central tenant/domain/subscription records
  -> record completion
```

The process must be retry-safe:

```text
already-deleted tenant -> safe skip
already-deleted file   -> safe skip
partial cleanup        -> retry remaining work
```

A failed cleanup must remain observable and retryable and must not be reported as successful.

## 13. Lifecycle Metadata

Recommended fields:

```text
started_at
trial_ends_at
read_only_ends_at
locked_at
delection_at
deleted_at
```

An operational `deletion_scheduled_at` marker may also be used. If hard deletion removes the tenant row, the final deletion event should remain auditable without retaining tenant business data.

## 14. Notifications

Progressive warnings should cover:

```text
trial active / ending soon
read-only started
read-only ending soon
locked / payment required
delection approaching
final deletion warning
```

Notification delivery failures must never corrupt lifecycle state transitions.

## 15. Frontend Rules

Trial:

```text
Normal workspace + trial banner + upgrade CTA
```

Read-only:

```text
Workspace visible + read-only banner + disabled write controls + upgrade CTA + deletion deadline
```

Locked:

```text
Payment wall + hidden business data + upgrade CTA + countdown + exact deletion deadline + support + logout
```

After verified payment, the existing workspace returns unchanged.

## 16. Backend Authorization

```text
TRIAL
  -> full authorized access

ACTIVE
  -> full authorized access

READ_ONLY
  -> reads allowed
  -> business writes denied

LOCKED
  -> normal workspace reads denied
  -> business writes denied
  -> billing/payment/support/logout allowed

DELETED
  -> terminal
```

The rule must cover both web and API access centrally.

## 17. Public/External Operations

Public operations that mutate tenant-owned business data must respect the lifecycle:

```text
trial     -> allowed subject to normal rules
read_only -> disabled
locked    -> disabled
```

Where middleware cannot cover the operation, enforce the rule in the business action itself.

## 18. Testing Contract

Tests must cover signup, all lifecycle transitions, server-side read-only/write blocking, locked read/write blocking, payment restoration, deletion, storage cleanup, retry/idempotency, and exact boundary timestamps.

Boundary cases:

```text
trial_ends_at - 1 second
trial_ends_at
trial_ends_at + 1 second
read_only_ends_at - 1 second
read_only_ends_at
read_only_ends_at + 1 second
delection_at - 1 second
delection_at
delection_at + 1 second
```

## 19. Timezone

Persist lifecycle timestamps consistently (recommended UTC), compare them using application-consistent Carbon logic, and convert only for display.

## 20. Final Canonical Rule

```text
Day 0       Signup
Day 0-7     Full Trial
Day 7-21    Read-Only
Day 21-27   Locked / Payment Required
Day 27      Permanent Deletion
```

Payment before deletion restores `active` and preserves the existing tenant and all existing tenant data.

This 27-day policy supersedes the older 51-day locked-window wording and must be treated as the canonical trial-retention contract.
