# Velora — Testing & QA Plan

## Objective

Every core business rule should be protected by automated tests and then verified through a clean production-like environment.

## Test Layers

### Unit

- [ ] Slot engine calculations.
- [ ] Time normalization.
- [ ] Business rule helpers.
- [ ] Subscription limit calculations.
- [ ] State transition rules.
- [ ] Report aggregation helpers.

### Feature

- [ ] Tenant authentication.
- [ ] Admin authorization.
- [ ] Staff authorization.
- [ ] Customer booking.
- [ ] Appointment lifecycle.
- [ ] Queue lifecycle.
- [Service CRUD.
- [Staff CRUD.
- [Settings updates.
- [Subscription lifecycle.
- [Billing checkout.
- [Billing callbacks/webhooks.
- [Trial extension.
- [Exports.
- [Locale switching.

### Integration

- [ ] Appointment + queue integration.
- [ ] Booking + staff schedule integration.
- [ ] Subscription + tenant middleware integration.
- [ ] Billing provider + subscription state integration.
- [ ] Queue + notifications/reminders where applicable.
- [ ] Tenant filesystem + uploaded assets.

### Security / Negative Tests

- [ ] Tenant A cannot read tenant B resources.
- [ ] Tenant A cannot update tenant B resources.
- [ ] Tenant A cannot delete tenant B resources.
- [ ] Staff cannot access Admin-only operations.
- [ ] Assistant cannot access Admin-only operations.
- [ ] Customer cannot call admin APIs.
- [ ] Invalid provider webhook is rejected.
- [ ] Duplicate webhook does not duplicate side effects.
- [ ] Booking cannot be created for unavailable slot.
- [ ] Concurrent booking of the same slot has deterministic outcome.

## High-Value Scenarios

### Booking

- [ ] Book a valid slot.
- [ ] Book an unavailable slot.
- [ ] Book with invalid service/staff combination.
- [ ] Book outside working hours.
- [ ] Book on a holiday.
- [ ] Cancel appointment.
- [ ] Reschedule appointment.
- [ ] Concurrent booking race.

### Queue

- [ ] Add to queue.
- [ ] Call next.
- [ ] Serve.
- [ ] Complete.
- [ ] Return to waiting.
- [ ] Priority ordering.
- [ ] Concurrent call-next race.

### Subscription

- [ ] Trial active.
- [ ] Trial expiry.
- [ ] Grace entry.
- [ ] Grace write restrictions.
- [ ] Grace expiry.
- [ ] Active renewal.
- [ ] Cancellation.
- [ ] Plan limit reached.
- [ ] Unlimited plan.

## Regression Testing

Every production bug should result in:

1. A reproducible test.
2. A code fix.
3. A regression test.
4. A release note entry when user-visible.

## QA Environments

Recommended:

- Local developer environment.
- CI environment.
- Staging environment using production-like configuration.
- Production.

## Clean-Checkout Validation

A release candidate must be tested from a clean checkout:

```text
composer install
npm ci
php artisan test
npm run build
```

The exact commands may be adjusted to the final CI/deployment environment.

## Browser QA

- [ ] Chrome desktop.
- [ ] Firefox desktop.
- [ ] Safari desktop where commercially relevant.
- [ ] Chrome Android / modern mobile browser.
- [ ] Safari iOS.
- [ ] RTL Arabic flow.
- [ ] LTR English flow.

## Performance QA

- [ ] Booking endpoint load test.
- [ ] Availability endpoint load test.
- [ ] Public queue polling load test.
- [ ] Dashboard load test.
- [ ] Large report/export test.
- [ ] Large tenant dataset test.
- [ ] Concurrent booking test.
- [ ] Concurrent queue call-next test.

## Release Gate

Do not release when:

- Any P0 security test fails.
- Any cross-tenant test fails.
- Payment webhook tests fail.
- A migration fails on a clean staging database.
- Production build fails.
- Critical queue jobs repeatedly fail.
- Database backup has not been verified.
