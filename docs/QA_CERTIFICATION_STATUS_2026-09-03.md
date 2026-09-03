# Velora — QA Certification Status — 2026-09-03

## Purpose

This document is the living execution record for the current Velora Master QA pass. It records verified local evidence, fixes applied during the current run, and remaining gates. It does not replace `docs/QA_FINDINGS_LOG.md` or the Master QA runbook.

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
| Queue stale-transition regression | `QueueLifecycleScenarioTest` | PASS | 7 | 12 | 2026-09-03 |

Current verified passing subtotal from completed passing focused runs remains **79 passed test executions / 267 assertions** for the previously reconciled suites. The newly confirmed `QueueLifecycleScenarioTest` run is tracked separately because it contains a new regression added after that subtotal was calculated. `CONC-001`/`CONC-002`/`CONC-003` remain CI-gated on Windows and are not counted in the local subtotal.

## Defects discovered and addressed during this pass

### QA-APT-001 — Queue skip observer downgraded no-show appointments to cancelled

**Scenario:** `APT-006` no-show lifecycle.

**Root cause:** Queue observer treated queue `skipped` as appointment `cancelled`, overwriting the terminal `no_show` state.

**Fix:** Preserve `Appointment::STATUS_NO_SHOW` when queue cleanup changes the linked queue row to `skipped`.

**Production commit:** `eabda6d8111fcc4c408294d55310431dcfc528bf`.

**Regression:** **5 tests / 24 assertions / 7.58s PASS**.

### QA-APT-002 — Reschedule left the linked Queue projection on the old date

**Scenario:** `APT-004` reschedule.

**Root cause:** Existing queue-date reconciliation handled cleanup but not normal reschedules.

**Fix:** `UpdateAdminAppointment` now updates an existing queue row's `queue_date` when the canonical appointment schedule changes.

**Production commit:** `c3525e19398fdd7313a8db9254c31c8aa27748b0`.

**Test correction:** `e9ab0e2a217be34d56e6530338419516d9e72430`.

**Final regression:** **6 tests / 29 assertions / 7.50s PASS**.

### QA-APT-003 — Completion/invoice reconciliation strengthened

**Scenario:** `APT-009` completion/invoice consistency.

**Assessment:** No production defect established. Regression now proves invoice linkage, amount, and initial billing state.

**Test commit:** `02befff2f299172f9b2677893bf3077b962a3c26`.

**Final regression:** **6 tests / 34 assertions / 7.54s PASS**.

### QA-BOOK-001 — Invalid resource booking regression

**Scenario:** `BOOK-005`.

**Final regression:** **6 tests / 9 assertions / 7.33s PASS**.

### QA-BOOK-002 — Timezone regression test contract + fixture defects

**Scenario:** `BOOK-011`.

**Classification:** Test/fixture contract defects; no production timezone defect established by the first run.

**Correction:** Real `staff_services` pivot shape plus raw UTC persistence assertion.

**Test correction commit:** `df16ee7b31ddda748135990421df5dc57d047640`.

**Final regression:** **7 tests / 12 assertions / 7.68s PASS**.

### QA-BOOK-003 — Booking notification failure isolation

**Scenario:** `BOOK-012`.

**First-run classification:** Test-data/schema contract defect because fixture value exceeded `appointments.source` length.

**Correction commit:** `8ec36b91b73bc6b7422a0f0f9b7740ebfb1884e6`.

**Final regression:** **2 tests / 19 assertions / 7.20s PASS**.

### QA-AVAIL-001 — Working-hours boundary matrix

**Scenario:** `AVAIL-001`.

**Coverage:** 09:00–17:00 window, 30-minute service, before opening, exact opening, last fitting slot, and closing overrun.

**Test commit:** `27d063ef50d6b562fb4e3278acb02846cfd564ec`.

**Final regression:** **5 tests / 14 assertions / 7.48s PASS**.

### QA-CONC-001 — Same-slot concurrent public booking race

**Scenario:** `CONC-001` two customers hit the same protected slot concurrently.

**Harness:** `BookingConcurrencyScenarioTest` + `concurrent_booking_worker.php`.

**Windows investigation:** Multiple local Windows/Herd executions failed before worker readiness. Direct `php tests/Support/concurrent_booking_worker.php` succeeds, while the PHPUnit/Symfony Process path exits before checkpoints. User environment is PHP 8.5.9 CLI.

**Important harness correction:** Independent child connections require parent fixtures to be committed before workers start. The ordering was corrected in commit `6100a711104c7cf05e217c6a12b1cc720b8a394e`.

**Platform policy:** The race test is skipped on Windows and enforced in Ubuntu/MySQL 8.4 CI. This is not a relaxation of the race assertion.

**Current state:** **PENDING CI EXECUTION. No local PASS claimed.**

### QA-CONC-002 — Duplicate concurrent booking submission

**Scenario:** `CONC-002` same customer, same slot, two concurrent submissions.

**Regression:** `DuplicateBookingConcurrencyScenarioTest` requires one success, one `SlotUnavailableException`, one active appointment, and one customer row.

**Test commit:** `e9360d6d28521d87280f5943b992ef80d7617ee0`.

**Platform policy:** Windows skipped; Ubuntu/MySQL 8.4 CI is enforcing.

**Current state:** **PENDING CI EXECUTION. No local PASS claimed.**

### QA-CONC-003 — Queue call-next concurrency

**Scenario:** `CONC-003` two staff requests call `CallNextQueueEntry` concurrently against one waiting row.

**Implementation under test:** `QueueRepository::callNext()` uses a DB transaction and `lockForUpdate()` before changing the selected waiting entry to `serving`.

**Regression:** `QueueConcurrencyScenarioTest.php` + `concurrent_queue_call_next_worker.php`. The invariant is exactly one worker receives the queue ID and the other receives `null`; the row ends in `serving`.

**Test commit:** `5ed636507e549e0a043f5340d43413ffaba5b98e`.

**Platform policy:** Windows skipped; Ubuntu/MySQL 8.4 CI is enforcing.

**Current state:** **PENDING CI EXECUTION. No local PASS claimed.**

### QA-QUEUE-001 — Stale queue transition race

**Scenario:** `CONC-004` queue mutation race. Two actors can hold different in-memory versions of one queue row and attempt incompatible transitions.

**Observed risk:** `TransitionQueueEntry` previously validated the transition against the caller's in-memory status and updated without a DB transaction or row lock. A stale actor could therefore overwrite a newer persisted state.

**Minimal production fix:** The action now starts a DB transaction, reloads the queue row with `lockForUpdate()`, validates against the locked persisted status, and only then updates the row.

**Production fix commit:** `60e6497ef7218a59283123abcf153c52e41f0261`.

**Regression:** `QueueLifecycleScenarioTest::stale_queue_model_cannot_overwrite_a_newer_terminal_transition()`.

**Regression commit:** `dbf711bd4166a2e4ccd700d2a2f7bf5fc4bd9ede`.

**Local verification:** **7 tests / 12 assertions / 7.51s PASS** on Windows/Herd PHP 8.5.9.

**Current state:** **Focused local regression PASS; fresh MySQL CI still required.**

### QA-CONC-005 — Concurrent webhook delivery gate

**Applicable provider paths:** Moyasar and Stripe both persist provider event IDs in `webhook_events` and use a unique `(provider,event_id)` constraint as the first idempotency boundary. Existing tests already cover duplicate webhook delivery behavior; the database uniqueness constraint is concurrency-safe at the storage layer.

**Current assessment:** The repository has a real concurrency-safe deduplication primitive, but there is not yet an independent multi-process processor race test in the current QA suite. This remains an explicit CI/certification gate rather than being marked PASS from sequential duplicate tests alone.

**Current state:** **PENDING concurrency race test + MySQL CI.**

## Current booking/appointment/queue gate status

### PASS — locally verified

- `BOOK-001` through `BOOK-012` listed above.
- `AVAIL-001` and `AVAIL-002` covered locally.
- Appointment lifecycle status machine and queue synchronization.
- `APT-004`, `APT-006`, `APT-008`, `APT-009`.
- Customer history/queue/invoice access.
- `CONC-004` focused stale-transition regression.

### OPEN / BLOCKED ON REGRESSION / CI

- `CONC-001` same-slot concurrent booking.
- `CONC-002` duplicate concurrent booking submission.
- `CONC-003` queue call-next concurrency.
- MySQL concurrency verification for `CONC-004`.
- `CONC-005` concurrent webhook delivery.
- Fresh Master QA MySQL 8.4 CI success for the final current `main`.
- Final cross-surface reconciliation and production certification.

## Next certification work

1. Confirm the new queue stale-transition fix in Master QA MySQL 8.4 CI.
2. Confirm `CONC-001`, `CONC-002`, and `CONC-003` in Ubuntu/MySQL 8.4 CI.
3. Add/run an independent concurrent webhook delivery race for the provider idempotency boundary.
4. Run the full `tests/Feature/QA` suite on a fresh MySQL 8.4 + PHP 8.4 CI run after all current changes settle.
5. Perform final cross-surface reconciliation and production certification.

## Evidence interpretation

A test failure is recorded as a defect until root cause is addressed. A test passing on one run does not retroactively certify a strengthened scenario after a new assertion reveals previously untested behavior.

## Working contract

Every new defect discovered in this pass must leave an audit trail containing scenario ID/area, observed failure, root cause, smallest correct fix, regression coverage, exact commit, observed local result, and CI status once available.
