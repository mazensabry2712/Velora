# Velora — Subscription Lifecycle & Data Retention Policy

## 1. Purpose

This document defines the mandatory lifecycle for every Velora tenant after signup.

The lifecycle is designed for Velora as a SaaS business-management platform. The tenant keeps its workspace and data throughout the trial, read-only, and locked periods. Access is progressively reduced to create a clear payment path while preserving the customer's work until the final deletion deadline.

The lifecycle is a platform-level rule and must be implemented consistently across Backend, Frontend, Billing, Authorization, Notifications, Scheduled Jobs, Storage, and Tests.

---

## 2. Canonical Lifecycle

Every newly registered tenant follows this timeline:

```text
DAY 0
  |
  v
SIGN UP
  |
  v
DAY 0 -> DAY 7
  |
  |  FULL ACCESS / TRIAL
  |  - Read
  |  - Create
  |  - Update
  |  - Delete
  |  - Operational actions
  |
  v
DAY 7
  |
  v
DAY 7 -> DAY 21
  |
  |  READ-ONLY MODE (14 DAYS)
  |  - Existing data remains visible
  |  - No business writes
  |  - No operational mutations
  |  - Billing remains available
  |
  v
DAY 21
  |
  v
DAY 21 -> DAY 51
  |
  |  LOCKED / PAYMENT REQUIRED (30 DAYS)
  |  - Business data is hidden behind a lock/paywall
  |  - No normal workspace access
  |  - Billing / subscription / payment / support / logout remain available
  |
  v
DAY 51
  |
  v
PERMANENT DELETION
  - Tenant deleted
  - Tenant-owned data deleted
  - Tenant-owned files deleted
  - Tenant storage cleaned
  - Tenant domain removed
  - Deletion is irreversible
```

### Total retention window

```text
7 days Trial
+ 14 days Read-Only
+ 30 days Locked
= 51 days from signup
```

The important boundary is that **30 locked days begin after the 14-day read-only period**.

Therefore, the final deletion deadline is **51 days after tenant signup** when the lifecycle is measured from account creation.

---

## 3. Lifecycle States

Velora must expose a deterministic access state for every tenant.

Recommended canonical states:

```text
trial
read_only
locked
active
cancelled
deleted
```

### 3.1 `trial`

The tenant is inside the first 7 days after signup.

Access level:

```text
FULL ACCESS
```

The tenant can use all capabilities allowed by its Velora subscription/product configuration.

### 3.2 `read_only`

The initial 7-day trial has ended and the tenant is inside the following 14-day preservation window.

Access level:

```text
READ ONLY
```

The tenant can inspect the workspace and existing data, but may not mutate business state.

### 3.3 `locked`

The 14-day read-only window has ended. The tenant enters a 30-day payment-required lock window.

Access level:

```text
LOCKED
```

Normal workspace data is not readable. The interface must display a payment wall/lock state instead of the underlying workspace content.

### 3.4 `active`

The tenant has a valid paid subscription.

Access level:

```text
FULL ACCESS
```

A successful verified payment restores normal access to the existing workspace and data.

### 3.5 `cancelled`

Cancellation behavior must be defined independently from trial expiration. Cancellation must not be treated as permanent deletion unless the deletion policy explicitly reaches its deadline.

### 3.6 `deleted`

The tenant has reached the final deletion deadline and the deletion workflow has completed.

This state is terminal.

---

## 4. Signup Rules

Signup creates the initial tenant environment and starts the lifecycle clock.

At signup, Velora creates or initializes the tenant's required platform records, including the tenant identity, owner/admin identity, tenant domain, initial subscription/trial record, settings, and other required workspace bootstrap data.

The lifecycle timestamp must be anchored to a deterministic field such as:

```text
subscription.started_at
```

or the tenant creation timestamp if that is the contractual signup timestamp.

The implementation must not calculate different lifecycle boundaries from unrelated timestamps.

### Mandatory signup result

```text
signup
  -> tenant exists
  -> admin exists
  -> domain exists
  -> trial exists
  -> trial_ends_at = signup_time + 7 days
  -> read_only_ends_at = trial_ends_at + 14 days
  -> deletion_at = read_only_ends_at + 30 days
```

Equivalent total offset from signup:

```text
trial_ends_at      = +7 days
read_only_ends_at  = +21 days
locked_ends_at     = +51 days
permanent_delete_at= +51 days
```

`locked_ends_at` and `permanent_delete_at` represent the same deadline for the canonical lifecycle.

---

## 5. Trial — Day 0 to Day 7

### User experience

The tenant sees the complete Velora workspace.

Typical capabilities include:

```text
Dashboard          FULL
Customers          FULL
Staff              FULL
Services           FULL
Appointments       FULL
Queue              FULL
Reports            FULL
Settings           FULL
Notifications      FULL
Subscription       FULL
Billing            FULL
```

### Writes allowed

All authorized business operations are allowed during the trial, subject to normal permissions and product limits.

Examples:

```text
Create customer       YES
Update customer       YES
Delete customer       YES
Create staff          YES
Update staff          YES
Delete staff          YES
Create service        YES
Update service        YES
Delete service        YES
Create appointment    YES
Update appointment    YES
Cancel/delete         YES
Queue operations      YES
Operational settings  YES
```

### Trial banner

The UI should clearly show the remaining trial period and provide a direct path to subscription/upgrade.

Recommended wording:

> Your Velora trial is active. You have X days remaining.

As the deadline approaches, the banner can become more prominent.

---

## 6. Read-Only Mode — Day 7 to Day 21

This is a 14-day preservation window immediately after trial expiration.

### Core business rule

**All tenant data remains available for viewing, but business writes are disabled.**

The purpose is to let the customer review their workspace and understand what they will regain after payment without deleting or hiding their work immediately.

### Allowed

```text
View dashboard           YES
View customers           YES
View staff               YES
View services            YES
View appointments        YES
View queue               YES
View reports             YES
View settings            YES
View subscription        YES
Open billing             YES
Start payment            YES
Contact support          YES
Logout                   YES
```

### Forbidden

```text
Create customer          NO
Update customer          NO
Delete customer          NO
Create staff             NO
Update staff             NO
Delete staff             NO
Create service           NO
Update service           NO
Delete service           NO
Create appointment       NO
Update appointment       NO
Delete appointment       NO
Queue mutation           NO
Settings mutation        NO
Profile destructive ops  NO
Any other business write NO
```

### Important security rule

The read-only restriction must be enforced on the **server**, not only by hiding buttons in the frontend.

Direct requests from the browser, Postman, scripts, mobile clients, or any other client must also be rejected.

Required behavior for blocked mutations:

```text
HTML/Form request -> 403 or controlled read-only response
JSON/API request  -> HTTP 403 with machine-readable error
```

Suggested error contract:

```json
{
  "success": false,
  "error": "SUBSCRIPTION_READ_ONLY",
  "message": "Your trial has ended. Your workspace is currently read-only.",
  "upgrade_url": "/admin/subscription/upgrade"
}
```

### Read-only banner

Recommended message:

> Your 7-day trial has ended. Your data is safe and available in read-only mode for 14 days. Upgrade to restore full access.

The banner should show:

```text
Read-only days remaining
Upgrade button
Subscription/Billing link
Final deletion date
```

The deletion date must be explicit so the customer knows the consequence of non-payment.

---

## 7. Locked Mode — Day 21 to Day 51

After the 14-day read-only window, the tenant enters a 30-day locked period.

### Core business rule

**The workspace data remains preserved, but normal reading is blocked behind a payment wall.**

This is intentionally stronger than read-only mode.

### Workspace visibility

Normal business pages should not expose their underlying data.

Examples:

```text
Dashboard          LOCKED
Customers          LOCKED
Staff              LOCKED
Services           LOCKED
Appointments       LOCKED
Queue              LOCKED
Reports            LOCKED
Settings           LOCKED
```

The user sees a controlled lock screen instead.

### Lock screen content

Recommended structure:

```text
Your Velora workspace is locked.

Your data is still preserved.

To restore access:
[ Upgrade / Pay Now ]

Your account is scheduled for permanent deletion on:
<exact date/time>

30-day locked period remaining: X days
```

### Exceptions

The tenant must retain access to:

```text
Subscription page
Billing page
Checkout
Payment verification/callback flow
Support/contact
Logout
```

No other workspace operation should expose or mutate business data in this state.

### Direct endpoint protection

Locked tenants must not bypass the lock by guessing URLs or calling APIs directly.

The authorization layer must reject protected reads and writes consistently.

Suggested API contract:

```json
{
  "success": false,
  "error": "SUBSCRIPTION_LOCKED",
  "message": "Your Velora workspace is locked. Please upgrade to restore access.",
  "upgrade_url": "/admin/subscription/upgrade",
  "deletion_at": "YYYY-MM-DDTHH:MM:SSZ"
}
```

---

## 8. Payment and Restoration

Payment is the only normal transition out of `read_only` or `locked` caused by trial expiration.

### Successful payment flow

```text
Customer clicks Upgrade
        |
        v
Subscription / Checkout
        |
        v
Promo Code (checkout only, if supported)
        |
        v
Selected payment gateway
        |
        v
Payment provider
        |
        v
Verified payment / webhook
        |
        v
Subscription becomes ACTIVE
        |
        v
Tenant access restored
```

### Gateway principle

The subscription lifecycle must not contain provider-specific business rules.

The application should ask its payment boundary to create/verify payment and then update subscription state after successful verification.

Current Velora strategy uses:

```text
Stripe
Fawry
PayPal
Moyasar
```

See `docs/PRODUCT-PLATFORM-STRATEGY.md` for the platform payment strategy.

### Restoration behavior

A successful verified payment must:

```text
set subscription = active
restore full access
preserve existing data
preserve existing tenant identity
preserve existing domain
preserve existing users
cancel the pending deletion schedule
```

The payment operation must not rebuild the tenant from scratch.

---

## 9. Promo Code Rule

Promo Codes belong to **Billing / Checkout**, not Signup.

Therefore:

```text
Signup page       -> NO promo code field
Signup request    -> NO promo code processing
Checkout page     -> Promo code MAY be available
Payment flow      -> Promo code validation and application
```

This keeps signup focused on tenant creation and trial activation.

---

## 10. Permanent Deletion — Day 51

At the end of the 30-day locked period, the tenant reaches its final deletion deadline.

### Deletion rule

**The tenant and all tenant-owned application data are deleted permanently.**

This is an irreversible operation under the application retention policy.

### Data categories to delete

The deletion workflow must identify and remove, as applicable:

```text
Tenant record
Tenant domain records
Tenant admin/tenant users
Tenant subscription records
Tenant customers
Tenant staff
Tenant services
Tenant appointments
Tenant queues
Tenant reports/data
Tenant settings
Tenant invoices
Tenant audit records that are tenant-owned
Tenant notifications
Tenant uploaded files
Tenant avatars
Tenant documents
Tenant attachments
Tenant generated exports
Tenant media
Tenant-specific cached/file artifacts
```

The exact list must follow the actual Velora data model and foreign-key relationships.

### File/storage deletion

Deletion is **not complete** when only database rows are removed.

Every tenant-owned filesystem object must be removed from all application-controlled storage locations, including any tenant-specific directories, uploaded media, documents, avatars, exports, and attachments.

The deletion job must also remove empty tenant storage directories after file cleanup where applicable.

The goal is:

```text
Database tenant data = deleted
Tenant files = deleted
Tenant-owned storage footprint = cleaned
Tenant domain = released
```

### No ongoing server storage

After successful deletion, Velora must not intentionally keep an active tenant copy on application storage.

The deletion process should verify that no tenant-owned files remain in application-managed storage locations.

---

## 11. Deletion Must Be Scheduled

Permanent deletion must be handled by a scheduled/background job, not by a user-facing HTTP request.

Recommended architecture:

```text
Scheduler
   |
   v
Find tenants where deletion_at <= now()
   |
   v
Delete tenant-owned files
   |
   v
Delete tenant-owned data
   |
   v
Delete tenant/domain records
   |
   v
Mark deletion complete / audit the operation
```

The job must be safe to retry.

### Idempotency requirement

If the deletion job runs twice for the same tenant, the second run must not break the cleanup process.

Example:

```text
Tenant already deleted
   -> skip safely

File already deleted
   -> skip safely
```

### Failure handling

Deletion must be observable.

Failures should be logged with enough context to identify the tenant and cleanup step, without logging secrets or payment credentials.

A failed deletion must not silently appear successful.

---

## 12. Deletion Warning Notifications

The tenant must receive progressive warnings before lock and permanent deletion.

Recommended schedule:

```text
During Trial
- Explain trial end date

Trial ending soon
- Explain transition to read-only

Read-only active
- Explain 14-day read-only window
- Show deletion deadline

Read-only ending soon
- Explain transition to locked

Locked active
- Show locked countdown
- Show exact deletion date
- Provide immediate payment CTA

Before deletion
- Send final warnings

Deletion
- Permanently delete the tenant according to this policy
```

The exact notification channels can include email, in-app banners, and other supported notification providers.

Notification delivery failure must not corrupt the subscription lifecycle.

---

## 13. Frontend Rules

The frontend should reflect the same lifecycle state supplied by the server.

### Trial UI

```text
Normal workspace
+ Trial banner
+ Upgrade CTA
```

### Read-only UI

```text
Normal workspace visible
+ Read-only banner
+ Disabled write controls
+ Upgrade CTA
+ Deletion deadline
```

Buttons that cause writes should be disabled or visually locked, but this is only UX. Server authorization remains mandatory.

### Locked UI

```text
Lock screen / payment wall
+ Data hidden
+ Upgrade CTA
+ Remaining locked days
+ Exact deletion deadline
+ Support option
+ Logout
```

### Successful payment

Immediately after verified payment:

```text
Lock/read-only state disappears
Workspace becomes accessible
Existing data is unchanged
```

---

## 14. Backend Authorization Rules

The subscription state must be enforced centrally.

Recommended conceptual policy:

```text
ACTIVE
  -> normal authenticated tenant access

TRIAL
  -> normal authenticated tenant access

READ_ONLY
  -> authenticated tenant access for read operations
  -> deny all business writes

LOCKED
  -> deny normal workspace reads
  -> deny all business writes
  -> allow billing/subscription/support/logout only

DELETED
  -> tenant no longer exists in the normal application model
```

The central access middleware/policy must cover both web and API routes.

Do not rely on individual controllers to remember subscription restrictions.

---

## 15. Public/External Operations

Public operations must be included in the lifecycle policy.

In particular, if an external customer can create or mutate tenant-owned business data, the system must define whether that capability is disabled in `read_only` and `locked` states.

For the canonical Velora policy:

```text
Tenant trial
  -> tenant-owned operational writes allowed

Tenant read_only
  -> tenant-owned operational writes disabled

Tenant locked
  -> tenant-owned operational writes disabled
```

This must be enforced server-side in the business action itself where middleware alone is not sufficient.

---

## 16. Subscription Date Model

Recommended fields for deterministic lifecycle calculations:

```text
started_at
trial_ends_at
read_only_ends_at
locked_at
locked_ends_at
scheduled_deletion_at
deleted_at
```

A paid subscription may additionally use:

```text
status
current_period_start
current_period_end
cancelled_at
```

The exact schema may differ, but the business meaning must remain explicit.

### Recommended invariant

```text
started_at < trial_ends_at
trial_ends_at < read_only_ends_at
read_only_ends_at = locked_at
locked_at < scheduled_deletion_at
```

For the canonical timeline:

```text
trial_ends_at       = started_at + 7 days
read_only_ends_at   = started_at + 21 days
locked_at            = started_at + 21 days
scheduled_deletion_at= started_at + 51 days
```

---

## 17. Access Decision Table

| Lifecycle | Data Read | Create | Update | Delete | Billing | Payment | Support | Logout |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Trial | YES | YES | YES | YES | YES | YES | YES | YES |
| Read-only | YES | NO | NO | NO | YES | YES | YES | YES |
| Locked | NO | NO | NO | NO | YES | YES | YES | YES |
| Active | YES | YES | YES | YES | YES | YES | YES | YES |
| Deleted | NO | NO | NO | NO | NO | NO | External process only | NO |

---

## 18. Security Requirements

The lifecycle must be enforced independently of frontend controls.

Mandatory tests include:

```text
Expired tenant + POST   -> blocked
Expired tenant + PUT    -> blocked
Expired tenant + PATCH  -> blocked
Expired tenant + DELETE -> blocked

Read-only tenant + direct API mutation -> blocked
Locked tenant + direct API read       -> blocked
Locked tenant + direct API mutation   -> blocked

User changes URL manually              -> still blocked
User calls endpoint via Postman        -> still blocked
User disables JS                       -> still blocked
```

Billing routes must remain reachable where required by the payment flow.

---

## 19. Scheduled Jobs

At minimum, the platform should have jobs/tasks equivalent to:

```text
subscription lifecycle synchronization
read-only/locked state evaluation
warning notification dispatch
permanent tenant deletion
tenant file/storage cleanup
```

Jobs should be scheduled using Laravel's scheduler/queue infrastructure.

The implementation must not depend on a browser request occurring on the exact expiration date.

---

## 20. Testing Specification

### Signup

```text
creates tenant
creates admin
creates trial
trial duration is exactly 7 days
lifecycle dates are calculated correctly
```

### Trial

```text
full access before trial_ends_at
full writes before trial_ends_at
```

### Read-only transition

```text
trial -> read_only at day 7
existing data remains
GET/read endpoints work
POST denied
PUT denied
PATCH denied
DELETE denied
billing works
payment works
```

### Locked transition

```text
read_only -> locked at day 21
workspace data hidden
normal dashboard blocked
normal business reads blocked
all writes blocked
billing works
payment works
support works
logout works
```

### Payment restoration

```text
read_only + successful payment -> active
locked + successful payment -> active
existing data preserved
scheduled deletion cancelled
full access restored
```

### Permanent deletion

```text
locked -> deleted at day 51
tenant removed
tenant domain removed
tenant data removed
tenant files removed
storage cleaned
repeat deletion job is safe
```

### Boundary tests

Test exact timestamps around:

```text
trial_ends_at - 1 second
trial_ends_at
trial_ends_at + 1 second
read_only_ends_at - 1 second
read_only_ends_at
read_only_ends_at + 1 second
scheduled_deletion_at - 1 second
scheduled_deletion_at
scheduled_deletion_at + 1 second
```

This prevents off-by-one-day and timezone errors.

---

## 21. Timezone Rules

All lifecycle comparisons must use a consistent application/server time basis.

The implementation must not mix local browser time, server time, database timezone, and provider timestamps without explicit conversion.

Recommended practice:

```text
Persist lifecycle timestamps in UTC
Convert to tenant/user locale for display
Perform comparisons using application-consistent timezone/Carbon logic
```

---

## 22. Current Repository Alignment Note

This document is the target product rule and supersedes older trial/grace assumptions.

The repository currently contains older lifecycle concepts that must be aligned with this policy before the lifecycle can be considered production-complete. In particular, the current implementation contains a `grace` state and a 3-day grace transition, while existing seeded subscription plans use trial durations other than 7 days.

These older behaviors must not remain as hidden alternate business rules.

The implementation target is:

```text
7-day trial
+
14-day read-only
+
30-day locked
+
permanent deletion at the end of the locked period
```

---

## 23. Product Principle

Velora should never delete a customer's work immediately when the trial ends.

The commercial journey is:

```text
TRY
  -> SEE VALUE
  -> READ-ONLY PRESERVATION
  -> PAYMENT WALL
  -> RESTORE ACCESS
  -> OTHERWISE PERMANENT DELETION
```

The product promise is therefore:

> Your data stays with you while you decide — but continued access requires an active subscription, and inactive accounts are permanently deleted after the defined retention window.

---

## 24. Final Canonical Rule

For every new Velora tenant:

```text
Day 0
  Signup

Day 0-7
  Full Access

Day 7-21
  Read-Only

Day 21-51
  Locked / Payment Required

Day 51
  Permanent Deletion
```

Payment at any point before the deletion deadline restores the tenant to `active` and preserves the existing tenant data.

After permanent deletion completes, the tenant and its tenant-owned data/files are no longer recoverable through the normal Velora application.
