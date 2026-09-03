# Velora — QA Certification Status — 2026-09-03

## Purpose

This document is the living execution record for the current Velora Master QA pass. It records verified local evidence, the fixes applied during the current run, and the remaining gates. It does not replace `docs/QA_FINDINGS_LOG.md` or the Master QA runbook.

## Certification rule

A feature family is not considered certified from test count alone. Required evidence is:

1. Happy-path behavior.
2. Negative and edge cases.
3. Database/data invariants.
4. Dependent projection reconciliation.
5. Regression coverage for discovered defects.
6. MySQL CI evidence for release certification.
7. Concurrency/security gates where applicable.

The canonical certification environment is MySQL 8.4 + PHP 8.4. The repository's Master QA workflow runs the `tests/Feature/QA` suite against MySQL 8.4.

## Verified local evidence — current session

| Area | Test suite | Result | Tests | Assertions | Evidence date |
|---|---|---:|---:|---:|---|
| Public booking journey | `CustomerBookingJourneyTest` | PASS | 5 | 41 | 2026-09-03 |
| Customer portal | `CustomerPortalJourneyTest` | PASS | 3 | 16 | 2026-09-03 |
| Appointment actions | `AppointmentActionsTest` | PASS | 13 | 37 | 2026-09-03 |
| Appointment/queue integration | `AppointmentQueueIntegrationTest` | PASS | 9 | 14 | 2026-09-03 |
| Booking rules (initial) | `BookingRulesScenarioTest` | PASS | 5 | 6 | 2026-09-03 |
| Booking availability rules (initial) | `BookingAvailabilityRulesScenarioTest` | PASS | 4 | 8 | 2026-09-03 |
| Booking availability rules (working-hours matrix) | `BookingAvailabilityRulesScenarioTest` | PASS | 5 | 14 | 2026-09-03 |
| Appointment lifecycle (pre-fix) | `AppointmentLifecycleScenarioTest` | PASS | 3 | 11 | 2026-09-03 |
| Appointment lifecycle (strengthened, pre-fix) | `AppointmentLifecycleScenarioTest` | FAIL | 3 passed + 1 failed | 15 | 2026-09-03 |
| Appointment lifecycle (post-fix no-show regression) | `AppointmentLifecycleScenarioTest` | PASS | 5 | 24 | 2026-09-03 |
| Appointment lifecycle (reschedule regression, first run) | `AppointmentLifecycleScenarioTest` | FAIL | 4 passed + 1 failed | 19 | 2026-09-03 |
| Appointment lifecycle (reschedule regression, corrected) | `AppointmentLifecycleScenarioTest` | PASS | 6 | 29 | 2026-09-03 |
| Appointment lifecycle (invoice reconciliation, strengthened) | `AppointmentLifecycleScenarioTest` | PASS | 6 | 34 | 2026-09-03 |
| Booking rules (resource regression) | `BookingRulesScenarioTest` | PASS | 6 | 9 | 2026-09-03 |
| Booking rules (timezone regression, first run) | `BookingRulesScenarioTest` | FAIL | 5 passed + 2 failed | 7 | 2026-09-03 |
| Booking rules (timezone regression, corrected) | `BookingRulesScenarioTest` | PASS | 7 | 12 | 2026-09-03 |
| Booking notification failure isolation (first run) | `BookingNotificationFailureScenarioTest` | FAIL | 0 | 0 | 2026-09-03 |
| Booking notification failure isolation (corrected fixture) | `BookingNotificationFailureScenarioTest` | PASS | 2 | 19 | 2026-09-03 |

Current verified passing subtotal from completed passing focused runs is **79 passed test executions / 267 assertions**. Failed intermediate runs are retained as evidence and are not counted as passes. `CONC-001`/`CONC-002` are CI-gated on Windows and therefore are not counted in this local subtotal.

## Defects discovered and addressed during this pass

### QA-APT-001 — Queue skip observer downgraded no-show appointments to cancelled

**Scenario:** `APT-006` no-show lifecycle.

**Initial observed failure:** The strengthened `AppointmentLifecycleScenarioTest` expected `appointment.status = no_show` after `ChangeAppointmentStatus::execute(..., no_show)`, but the persisted appointment became `cancelled`.

**Root cause:** `app/Models/Queue.php` treated every transition to `cancelled` or `skipped` as an instruction to set the linked appointment status to `cancelled`. `ChangeAppointmentStatus` first set the appointment to `no_show`, then set its queue row to `skipped`; the Queue observer then overwrote the terminal appointment state.

**Fix implemented:** The Queue observer now preserves `Appointment::STATUS_NO_SHOW` when the linked appointment is already `no_show`. Queue cleanup still produces `skipped`, while the appointment lifecycle remains `no_show`.

**Production commit:** `eabda6d8111fcc4c408294d55310431dcfc528bf`.

**Regression result:** **5 tests / 24 assertions / 7.58s PASS**.

### QA-APT-002 — Reschedule left the linked Queue projection on the old date

**Scenario:** `APT-004` reschedule.

**Observed gap:** The existing reschedule path recalculated the canonical schedule but did not move an existing linked Queue row's `queue_date`.

**Root cause:** Queue reconciliation existed only for the past-date cleanup branch.

**Production fix:** `UpdateAdminAppointment` now updates an existing queued appointment's `queue_date` whenever the appointment schedule changes.

**Production commit:** `c3525e19398fdd7313a8db9254c31c8aa27748b0`.

**Test correction:** An initial regression assertion compared a cast Carbon `date` value to a string; it was corrected to compare normalized date strings. Test correction commit: `e9ab0e2a217be34d56e6530338419516d9e72430`.

**Final regression:** **6 tests / 29 assertions / 7.50s PASS**.

**Final state:** **APT-004 PASS locally.**

### QA-APT-003 — Completion/invoice reconciliation strengthened

**Scenario:** `APT-009` completion/invoice consistency.

**Reason for strengthening:** Count-only coverage did not prove invoice linkage, amount, or billing state.

**Regression added:** Persisted invoice identity, customer/appointment linkage, service-price amount, and initial `pending` status are now checked.

**Production assessment:** No production defect was established by this strengthening pass.

**Test commit:** `02befff2f299172f9b2677893bf3077b962a3c26`.

**Final regression:** **6 tests / 34 assertions / 7.54s PASS**.

**Final state:** **APT-009 PASS locally.**

### QA-BOOK-001 — Invalid resource booking regression

**Scenario:** `BOOK-005` invalid resource rejected.

**Regression added:** An active resource not assigned to the selected service is supplied and the booking must fail before an appointment is created.

**Final regression:** **6 tests / 9 assertions / 7.33s PASS**.

**Final state:** **BOOK-005 PASS locally.**

### QA-BOOK-002 — Timezone regression test contract + fixture defects

**Scenario:** `BOOK-011` customer-requested timezone conversion.

**First-run failures:**

1. The resource fixture attempted to insert `user_id` into `staff_services`, but the tenant schema does not contain that column.
2. The timezone regression compared a DB-reloaded `starts_at` Carbon object directly with a Carbon instance in a different timezone context, which was not a valid proof of the raw UTC persistence contract.

**Classification:** Both were **test/fixture contract defects**. The run did not establish a production timezone defect.

**Correction:** The resource fixture now uses the real `staff_services` pivot schema. The timezone regression now compares the raw persisted UTC instant and separately verifies staff-local compatibility fields.

**Test correction commit:** `df16ee7b31ddda748135990421df5dc57d047640`.

**Final regression execution:** **7 tests / 12 assertions / 7.68s PASS**.

**Final state:** **BOOK-011 PASS locally.**

### QA-BOOK-003 — Booking notification failure isolation

**Scenario:** `BOOK-012` booking notification failure does not corrupt the booking core.

**Regression added:** `tests/Feature/QA/BookingNotificationFailureScenarioTest.php` covers both Email and WhatsApp failure behavior.

**First run:** Test fixtures violated `appointments.source` length and failed before entering the notification jobs.

**Classification:** **Test-data/schema contract defect.**

**Correction:** Fixture source shortened to `qa-notify-fail`.

**Fixture correction commit:** `8ec36b91b73bc6b7422a0f0f9b7740ebfb1884e6`.

**Final regression:** **2 tests / 19 assertions / 7.20s PASS**.

**Final state:** **BOOK-012 PASS locally.**

### QA-AVAIL-001 — Working-hours boundary matrix

**Scenario:** `AVAIL-001` working-hours validity at the exact operating window boundaries.

**Regression added:** `BookingAvailabilityRulesScenarioTest` explicitly covers the 09:00–17:00 operating window with a 30-minute service: before opening, exact opening, last fitting slot, and a slot that overruns closing.

**Final regression:** **5 tests / 14 assertions / 7.48s PASS**.

**Test commit:** `27d063ef50d6b562fb4e3278acb02846cfd564ec`.

**Final state:** **AVAIL-001 PASS locally for the covered working-hours boundary contract.**

### QA-CONC-001 — Same-slot concurrent public booking race

**Scenario:** `CONC-001` two customers attempt the same protected booking slot concurrently.

**Harness:** `tests/Feature/QA/BookingConcurrencyScenarioTest.php` launches two independent PHP worker processes, synchronizes them with a filesystem barrier, and requires exactly one successful booking plus exactly one `SlotUnavailableException` with one active appointment persisted.

**Implementation under test:** `BookingCreationService` runs inside a DB transaction and locks overlapping appointment rows with `lockForUpdate()` before validation and creation.

**Local investigation:** Multiple Windows/Herd runs failed before worker readiness. The user's environment reports PHP 8.5.9 CLI. Direct worker invocation succeeds, while the PHPUnit/Symfony Process path does not produce worker checkpoints.

**Harness correction:** An additional ordering defect was identified: child workers must not start until the parent has committed the tenant and fixture rows because each worker uses an independent DB connection. `CONC-001` was corrected accordingly in commit `6100a711104c7cf05e217c6a12b1cc720b8a394e`.

**Platform policy:** The test explicitly skips on Windows and remains enforced in the Ubuntu/MySQL 8.4 Master QA workflow. This is a test-environment constraint, not a relaxation of the concurrency assertion.

**Current state:** **PENDING CI EXECUTION. No local PASS claimed.**

### QA-CONC-002 — Duplicate concurrent booking submission

**Scenario:** `CONC-002` the same customer submits the same booking concurrently.

**Regression added:** `tests/Feature/QA/DuplicateBookingConcurrencyScenarioTest.php` uses two independent workers with the same customer email and protected slot, requiring exactly one successful appointment, one `SlotUnavailableException`, one active appointment, and one customer row for the email.

**Harness correction:** Fixture rows are committed before spawning workers so independent DB connections can see the same committed state. Final test commit: `e9360d6d28521d87280f5943b992ef80d7617ee0`.

**Platform policy:** Windows execution is explicitly skipped; CI/Linux is the enforcing environment.

**Current state:** **PENDING CI EXECUTION. No local PASS claimed.**

## Previously verified local evidence

The focused local runs below are the verified evidence set in the current development environment:

```text
CustomerBookingJourneyTest → 5 passed / 41 assertions
CustomerPortalJourneyTest → 3 passed / 16 assertions
AppointmentActionsTest → 13 passed / 37 assertions
AppointmentQueueIntegrationTest → 9 passed / 14 assertions
BookingRulesScenarioTest (initial) → 5 passed / 6 assertions
BookingRulesScenarioTest (resource regression) → 6 passed / 9 assertions / 7.33s
BookingRulesScenarioTest (timezone regression, corrected) → 7 passed / 12 assertions / 7.68s
BookingAvailabilityRulesScenarioTest (working-hours matrix) → 5 passed / 14 assertions / 7.48s
AppointmentLifecycleScenarioTest (pre-strengthening) → 3 passed / 11 assertions / 7.23s
AppointmentLifecycleScenarioTest (post-fix no-show regression) → 5 passed / 24 assertions / 7.58s
AppointmentLifecycleScenarioTest (corrected reschedule regression) → 6 passed / 29 assertions / 7.50s
AppointmentLifecycleScenarioTest (strengthened invoice reconciliation) → 6 passed / 34 assertions / 7.54s
BookingNotificationFailureScenarioTest (corrected fixture) → 2 passed / 19 assertions / 7.20s
```

These are local MySQL-backed runs. They are not release certification without fresh MySQL CI evidence.

## Current booking/appointment gate status

### PASS — locally verified

- `BOOK-001` real public booking journey.
- `BOOK-002` duplicate booking protection.
- `BOOK-003` inactive service cannot book.
- `BOOK-004` staff/service mismatch rejected.
- `BOOK-005` invalid resource rejected.
- `BOOK-006` past/out-of-window booking rule coverage where currently implemented.
- `BOOK-007` same-day booking policy.
- `BOOK-008` minimum advance rule.
- `BOOK-009` maximum advance rule.
- `BOOK-010` occupied slot rejected.
- `BOOK-011` customer timezone conversion.
- `BOOK-012` booking notification failure isolation.
- `AVAIL-001` working-hours boundary matrix for the covered operating window.
- `AVAIL-002` holiday blocking and related availability rules.
- Appointment lifecycle status-machine validation.
- Confirm/cancel/complete queue synchronization coverage.
- `APT-006` no-show lifecycle.
- `APT-004` reschedule with queue-date reconciliation.
- `APT-008` persisted status-history completeness.
- `APT-009` completion/invoice consistency.
- Appointment/queue integration lifecycle.
- Customer history/queue/invoice access.
- Appointment action and queue synchronization coverage.

### OPEN / BLOCKED ON REGRESSION / CI

- `CONC-001` same-slot concurrent booking — CI/Linux execution required.
- `CONC-002` duplicate concurrent booking submission — CI/Linux execution required.
- `CONC-003` queue call-next concurrency.
- `CONC-004` queue mutation race cases.
- `CONC-005` concurrent webhook delivery where applicable.
- Fresh Master QA MySQL 8.4 CI success for current `main`.
- Final cross-surface reconciliation and production certification.

## Next certification work

1. Execute `CONC-001` and `CONC-002` in the Master QA Ubuntu/MySQL 8.4 environment.
2. Add/run queue call-next and queue mutation race coverage.
3. Add/run concurrent webhook delivery where applicable.
4. Run full `tests/Feature/QA` on fresh MySQL 8.4 + PHP 8.4 CI.
5. Perform final cross-surface reconciliation and production certification.

## Concurrency / certification gates still outstanding

- Same protected booking slot under concurrent requests (`CONC-001`).
- Duplicate concurrent booking submission (`CONC-002`).
- Queue call-next concurrency (`CONC-003`).
- Queue mutation race cases (`CONC-004`).
- Concurrent webhook delivery where applicable (`CONC-005`).
- Fresh Master QA MySQL 8.4 CI success for the current `main` SHA.
- Final cross-surface reconciliation and production certification.

## Evidence interpretation

A test failure is recorded as a defect until root cause is addressed. A test passing on one run does not retroactively certify a strengthened scenario after a new assertion reveals a previously untested behavior.

The Master QA workflow is configured for MySQL 8.4 + PHP 8.4 and runs the complete `tests/Feature/QA` suite.

## Working contract

Every new defect discovered in this pass must leave an audit trail containing:

- scenario ID / area,
- observed failure,
- root cause,
- smallest correct fix,
- regression coverage,
- exact commit,
- observed local result,
- CI status once available.
