# Architecture Migration — Current State

This document tracks the current modular-monolith migration after the initial architecture merge.

## Completed

- Application Actions introduced for core booking, queue, reporting, subscription, pricing and tenant flows.
- Transaction boundary introduced.
- Payment resolver contract introduced.
- Subscription side effects moved behind events/listeners.
- Global layout data access moved out of AppServiceProvider.
- Direct queue admission moved into an application use case.
- Typed DTOs added for complex write flows.
- Appointment command persistence boundary added.

## Next

- Extract appointment status and queue commands into actions.
- Extract queue reporting queries.
- Complete customer/staff administration boundaries.
- Harden webhook idempotency and tenant-aware jobs.
- Add architecture, localization and tenant-isolation tests.
