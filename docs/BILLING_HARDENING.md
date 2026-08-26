# Velora — Billing & Subscription Hardening

## Objective

Billing must be deterministic, auditable and safe against duplicate callbacks, browser manipulation and provider retries.

## Source of Truth

Payment state must be controlled by verified payment-provider events, not by a browser redirect alone.

- [ ] Stripe webhook signature verification.
- [ ] Moyasar callback/webhook authenticity verification.
- [ ] Browser success pages never directly mark money as paid.
- [ ] Provider event IDs are stored for deduplication.
- [ ] Duplicate webhook delivery is harmless.
- [ ] Out-of-order events are handled safely.

## Subscription State Machine

Document and test these states:

- `trial`
- `active`
- `grace`
- `expired`
- `cancelled`

### Trial

- [ ] Trial start is deterministic.
- [ ] Trial end is deterministic.
- [ ] Trial extension can only happen when authorized.
- [ ] Trial cannot be extended repeatedly through request replay.
- [ ] Day-based founder alerts are idempotent.

### Active

- [ ] Renewal extends the correct period.
- [ ] Successful payment activates the correct tenant subscription.
- [ ] Existing tenant remains attached to the correct plan.

### Grace

- [ ] Grace starts only once when the active period ends.
- [ ] Grace duration is centralized/configurable.
- [ ] Write operations are restricted according to product policy.
- [ ] Upgrade during grace restores access correctly.

### Expired

- [ ] Expired tenants are blocked from protected product routes.
- [ ] Billing routes remain accessible.
- [ ] Data is retained according to product retention policy.
- [ ] Re-activation restores access without creating corrupted duplicate subscriptions.

### Cancelled

- [ ] Cancellation semantics are documented.
- [ ] Immediate vs end-of-period cancellation is explicit.
- [ ] Access rules are deterministic.

## Idempotency

Every provider event handler must be safe to execute multiple times.

Recommended pattern:

1. Verify provider signature.
2. Extract provider event/payment ID.
3. Start transaction.
4. Check whether event was already processed.
5. If processed, return success without repeating side effects.
6. Apply state transition.
7. Record event.
8. Commit.

## Invoice / Transaction Model

The current subscription history should be reviewed against the product's commercial requirements.

If formal invoices are needed, create a dedicated model/table structure containing at minimum:

- Invoice number.
- Tenant ID.
- Provider invoice ID.
- Provider transaction/payment ID.
- Subscription ID.
- Plan ID.
- Amount.
- Currency.
- Tax when applicable.
- Status.
- Issued at.
- Due at when applicable.
- Paid at.
- Failed at.
- Metadata.

## Storage Limits

The current subscription code exposes a storage limit but does not yet calculate real storage usage.

- [ ] Define what counts toward quota.
- [ ] Implement tenant storage calculation.
- [ ] Cache expensive storage calculations.
- [ ] Enforce quota on uploads.
- [ ] Allow safe cleanup after deletion.
- [ ] Add quota tests.

## Plan Limits

- [ ] User limit enforced in every user-creation path.
- [ ] Appointment limit enforced in every appointment-creation path.
- [ ] Storage limit enforced in upload paths.
- [ ] Unlimited plans behave consistently.
- [ ] Limit warnings are consistent across dashboard/API/UI.
- [ ] Limit checks are transaction-safe where races are possible.

## Upgrade / Downgrade

- [ ] Upgrade to a higher plan.
- [ ] Handle same-plan purchase safely.
- [ ] Prevent invalid downgrade when usage exceeds the target plan.
- [ ] Define immediate vs next-cycle plan changes.
- [ ] Preserve customer/provider IDs correctly.
- [ ] Keep historical invoices attached to the old plan.

## Payment Failure

- [ ] Failed payment state is recorded.
- [ ] Customer/admin is notified appropriately.
- [ ] Retry events do not duplicate charges in internal state.
- [ ] Grace lifecycle starts according to policy.

## Refunds / Chargebacks

- [ ] Define refund behavior.
- [ ] Define partial refund behavior if supported.
- [ ] Define chargeback handling.
- [ ] Ensure refund does not accidentally create an active subscription.
- [ ] Keep an auditable history.

## Operational Requirements

- [ ] Payment webhook failures produce alerts.
- [ ] Failed events can be retried safely.
- [ ] Billing logs exclude secrets/payment credentials.
- [ ] Reconciliation job compares provider state and internal state.
- [ ] Manual support workflow exists for exceptional payment cases.

## Billing Exit Criteria

- Webhook-first state transitions are verified.
- Duplicate events are harmless.
- Trial/grace/expiry tests pass.
- Upgrade/downgrade tests pass.
- Payment failure tests pass.
- Cross-tenant billing isolation tests pass.
- Reconciliation procedure is documented.
