# Architecture Migration Checklist

This checklist is the migration source of truth. It intentionally tracks the transition from legacy framework-shaped code toward bounded modules.

## Completed

- [x] Application Actions introduced for booking, pricing, tenant registration, queue, reporting and subscription.
- [x] Transaction boundary introduced.
- [x] Payment gateway resolver contract introduced.
- [x] Subscription upgrade persistence behind a port.
- [x] Subscription side effects moved to event/listener boundary.
- [x] Landing/Admin view data moved out of `AppServiceProvider`.
- [x] Direct queue admission moved to an Application Action.
- [x] Appointment command persistence boundary introduced.
- [x] Typed DTOs introduced for complex write workflows.

## Next migrations

- [ ] Move Appointment `update` and `quickStatus` orchestration into Actions.
- [ ] Move Appointment `addToQueue` and `removeFromQueue` orchestration into Actions.
- [ ] Move Queue `days` aggregation into a reporting/query service.
- [ ] Move Queue `updateEntry`, priority and deletion commands behind use cases.
- [ ] Isolate Customer and Staff administration into bounded Application modules.
- [ ] Complete Payment webhook normalization and idempotency boundary.
- [ ] Introduce explicit tenant context for queued jobs touching tenant data.
- [ ] Add architecture dependency tests.
- [ ] Add translation-key contract tests.
- [ ] Add tenant-isolation integration tests.
- [ ] Run full regression suite before deleting legacy paths.

## Deletion rule

A legacy service/repository/controller method may be removed only when code search shows no remaining callers and tests cover the replacement behavior.
