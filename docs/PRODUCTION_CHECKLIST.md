# Velora — Production Launch Checklist

## Application

- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] `APP_URL` is correct.
- [ ] HTTPS enabled.
- [ ] Trusted proxies / host configuration reviewed.
- [ ] Production error page verified.

## Database

- [ ] Central database backup configured.
- [ ] Tenant database backup configured.
- [ ] Restore procedure tested.
- [ ] Migration process tested on staging.
- [ ] Database credentials use least privilege.
- [ ] Slow query monitoring available.
- [ ] Required indexes verified.

## Tenancy

- [ ] Central domains configured correctly.
- [ ] Tenant domains configured correctly.
- [ ] Cross-tenant read test passes.
- [ ] Cross-tenant write test passes.
- [ ] Cross-tenant delete test passes.
- [ ] Tenant files isolated.
- [ ] Tenant cache isolated.
- [ ] Tenant queues preserve context.

## Authentication / Security

- [ ] Login throttling active.
- [ ] CSRF active for web forms.
- [ ] Secure cookies configured.
- [ ] Password reset tested.
- [ ] Role permissions tested.
- [ ] Object-level authorization tested.
- [ ] Upload restrictions verified.
- [ ] Security headers reviewed.
- [ ] Secrets absent from git.

## Booking

- [ ] Public booking works.
- [ ] Invalid service rejected.
- [ ] Invalid staff/service combination rejected.
- [ ] Unavailable slots rejected.
- [ ] Double-booking race tested.
- [ ] Timezone behavior tested.
- [ ] Cancellation tested.
- [ ] Rescheduling tested.

## Queue

- [ ] Public queue status works.
- [ ] Call-next works.
- [ ] Priority ordering works.
- [ ] Concurrent call-next tested.
- [ ] Queue state transitions validated.

## Billing

- [ ] Stripe credentials configured.
- [ ] Moyasar credentials configured if enabled.
- [ ] Webhook endpoints configured.
- [ ] Webhook signature verification tested.
- [ ] Duplicate webhook tested.
- [ ] Renewal tested.
- [ ] Failed payment tested.
- [ ] Trial expiry tested.
- [ ] Grace expiry tested.
- [ ] Upgrade tested.
- [ ] Cancellation tested.
- [ ] Reconciliation procedure tested.

## Files / Storage

- [ ] Storage disks configured.
- [ ] Tenant asset isolation verified.
- [ ] Upload size limits verified.
- [ ] Storage usage tracking implemented if quota is sold.
- [ ] Backups include required assets.

## Queue Workers / Scheduler

- [ ] Queue worker process configured.
- [ ] Worker restart strategy configured.
- [ ] Scheduler running every minute or as required.
- [ ] Failed jobs monitored.
- [ ] Critical scheduled commands verified.

## Frontend

- [ ] `npm ci` succeeds.
- [ ] `npm run build` succeeds.
- [ ] Vite assets load in production.
- [ ] Mobile booking tested.
- [ ] Admin responsive layout tested.
- [ ] Arabic / RTL tested.
- [ ] Supported languages tested.

## Monitoring

- [ ] Error tracking active.
- [ ] Application logs centralized.
- [ ] Queue failures alerting active.
- [ ] Payment webhook failures alerting active.
- [ ] Uptime monitoring active.
- [ ] Database health monitored.

## Release

- [ ] Full test suite passes.
- [ ] Staging smoke test passes.
- [ ] Production backup completed immediately before deploy.
- [ ] Migration plan reviewed.
- [ ] Rollback plan reviewed.
- [ ] Release/tag created.
- [ ] Post-deploy smoke test completed.

## Go / No-Go

### GO

Only when every P0 security and billing item is verified and all critical tests pass.

### NO-GO

Do not launch when:

- Cross-tenant access has not been ruled out.
- Payment state can be changed by an unverified browser callback.
- Production backups cannot be restored.
- Critical queue workers are not monitored.
- The full test/build pipeline is failing.
