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
| Booking rules | `BookingRulesScenarioTest` | PASS | 5 | 6 | 2026-09-03 |
| Booking availability rules | `BookingAvailabilityRulesScenarioTest` | PASS | 4 | 8 | 2026-09-03 |
| Appointment lifecycle (pre-fix) | `AppointmentLifecycleScenarioTest` | PASS | 3 | 11 | 2026-09-03 |
| Appointment lifecycle (strengthened, pre-fix) | `AppointmentLifecycleScenarioTest` | FAIL | 3 passed + 1 failed | 15 | 2026-09-03 |
| Appointment lifecycle (post-fix no-show regression) | `AppointmentLifecycleScenarioTest` | PASS | 5 | 24 | 2026-09-03 |
| Appointment lifecycle (reschedule regression, first run) | `AppointmentLifecycleScenarioTest` | FAIL | 4 passed + 1 failed | 19 | 2026-09-03 |
| Appointment lifecycle (reschedule regression, corrected) | `AppointmentLifecycleScenarioTest` | PASS | 6 | 29 | 2026-09-03 |

Current verified passing subtotal remains **53 passed tests / 186 assertions** from completed passing focused runs. The newer invoice-reconciliation assertions have been added but their local execution result is not yet observed.

## Defects discovered and addressed during this pass

### QA-APT-001 — Queue skip observer downgraded no-show appointments to cancelled

**Scenario:** `APT-006` no-show lifecycle.

**Initial observed failure:** The strengthened `AppointmentLifecycleScenarioTest` expected `appointment.status = no_show` after `ChangeAppointmentStatus::execute(..., no_show)`, but the persisted appointment became `cancelled`.

**Root cause:** `app/Models/Queue.php` treated every transition to `cancelled` or `skipped` as an instruction to set the linked appointment status to `cancelled`. `ChangeAppointmentStatus` first set the appointment to `no_show`, then set its queue row to `skipped`; the Queue observer then overwrote the terminal appointment state.

**Fix implemented:** The Queue observer now preserves `Appointment::STATUS_NO_SHOW` when the linked appointment is already `no_show`. Queue cleanup still produces `skipped`, while the appointment lifecycle remains `no_show`.

**Production commit:** `eabda6d8111fcc4c408294d55310431dcfc528bf`.

**Regression result:** After pulling `origin/main` at `66bce51aaa9595b000ec10dc2f03a56d93941a9d`, clearing caches, and running the strengthened lifecycle suite, **5 tests / 24 assertions passed** in 7.58s.

### QA-APT-002 — Reschedule left the linked Queue projection on the old date

**Scenario:** `APT-004` reschedule.

**Observed gap:** The existing reschedule path in `UpdateAdminAppointment` recalculated the canonical `starts_at`, `date`, and `time_slot`, but did not move an existing linked Queue row's `queue_date` to the new appointment date. That could leave the appointment and queue projections representing different business dates.

**Root cause:** Queue reconciliation existed only for the past-date cleanup branch; there was no update of `queue_date` when a future appointment with an existing Queue entry was moved to another date.

**Production fix implemented:** `UpdateAdminAppointment` now updates the existing Queue `queue_date` whenever the appointment schedule changes and the appointment remains queued.

**Production commit:** `c3525e19398fdd7313a8db9254c31c8aa27748b0`.

**First regression execution:** Pulled `origin/main` at `7367dbf48a517ee7b41edcfecd1de93d2afadae4` and ran the strengthened lifecycle suite. Four tests passed, then the new reschedule scenario failed at the assertion comparing `newStartsAt->toDateString()` with `fresh->date`.

**Observed failure:**

```text
Failed asserting that Illuminate\\Support\\Carbon Object ... is identical to '2026-09-06'
```

**Root cause classification:** This was a **test-contract defect**, not evidence of a production schedule mismatch. `Appointment::$casts` intentionally casts the legacy `date` column to a Carbon date object. The regression used `assertSame()` against a string instead of comparing normalized date values.

**Regression correction:** The assertion was changed to compare normalized date strings using the model's existing cast semantics.

**Test correction commit:** `e9ab0e2a217be34d56e6530338419516d9e72430`.

**Final regression execution:** Pulled `origin/main` at `08ea3bb94e3aea1caa676a0eaa1004a99049b117`, cleared caches, and ran:

```text
php -d memory_limit=512M artisan test tests/Feature/QA/AppointmentLifecycleScenarioTest.php --stop-on-failure

PASS Tests\\Feature\\QA\\AppointmentLifecycleScenarioTest
Tests:    6 passed (29 assertions)
Duration: 7.50s
```

**Final state:** **APT-004 PASS locally.** The regression verifies canonical `starts_at`, legacy compatibility `date`/`time_slot`, queue `queue_date` reconciliation, and preservation of the explicit confirmed status.

### QA-APT-003 — Completion/invoice reconciliation strengthened

**Scenario:** `APT-009` completion/invoice consistency.

**Reason for strengthening:** The prior lifecycle test only verified that the invoice count increased by one. That did not prove the persisted invoice was linked to the correct customer and appointment, carried the expected service amount, and had the expected initial billing status.

**Regression added:** The completed-appointment scenario now verifies:

- exactly one additional invoice exists for the appointment,
- the persisted invoice exists and is linked to the canonical customer,
- the persisted invoice references the completed appointment,
- invoice amount matches the configured service price,
- invoice status starts as `pending`.

**Production assessment:** No production defect has been established by this strengthening pass. The existing production path creates the invoice from the appointment's customer and service price when the appointment reaches `completed`.

**Test commit:** `02befff2f299172f9b2677893bf3077b962a3c26`.

**Current state:** **Re-run required.** No local result has yet been observed for the strengthened assertions.

## Previously verified local evidence

The following focused runs were completed successfully in the current development environment:

```text
CustomerBookingJourneyTest → 5 passed / 41 assertions
CustomerPortalJourneyTest → 3 passed / 16 assertions
AppointmentActionsTest → 13 passed / 37 assertions
AppointmentQueueIntegrationTest → 9 passed / 14 assertions
BookingRulesScenarioTest → 5 passed / 6 assertions / 7.56s
BookingAvailabilityRulesScenarioTest → 4 passed / 8 assertions / 7.33s
AppointmentLifecycleScenarioTest (pre-strengthening) → 3 passed / 11 assertions / 7.23s
AppointmentLifecycleScenarioTest (post-fix no-show regression) → 5 passed / 24 assertions / 7.58s
AppointmentLifecycleScenarioTest (corrected reschedule regression) → 6 passed / 29 assertions / 7.50s
```

These are local MySQL-backed runs. They are not release certification without fresh MySQL CI evidence.

## Current booking/appointment gate status

### PASS — locally verified

- `BOOK-001` real public booking journey.
- `BOOK-002` duplicate booking protection.
- `BOOK-003` inactive service cannot book.
- `BOOK-004` staff/service mismatch rejected.
- `BOOK-006` past/out-of-window booking rule coverage where currently implemented.
- `BOOK-007` same-day booking policy.
- `BOOK-008` minimum advance rule.
- `BOOK-009` maximum advance rule.
- `BOOK-010` occupied slot rejected.
- `AVAIL-002` holiday blocking and related availability rules.
- Appointment lifecycle status-machine validation.
- Confirm/cancel/complete queue synchronization coverage.
- `APT-006` no-show lifecycle, including `no_show` persistence, `no_show_at`, and queue `skipped` synchronization.
- `APT-004` reschedule, including canonical schedule and queue-date reconciliation.
- Appointment status-history completeness for the strengthened lifecycle scenario.
- Appointment completion/invoice creation coverage at the lifecycle level.
- Appointment/queue integration lifecycle.
- Customer history/queue/invoice access.
- Appointment action and queue synchronization coverage.

### OPEN / BLOCKED ON REGRESSION

- `APT-009` completion/invoice consistency — strengthened persisted-data reconciliation re-run required.
- `BOOK-005` invalid resource rejected.
- `BOOK-011` timezone conversion.
- `BOOK-012` booking notification failure does not corrupt booking.
- `AVAIL-001` working-hours matrix beyond current negative-boundary coverage.
- Any final reconciliation coverage not yet represented in the focused runs above.

## Next certification work

1. Re-run the strengthened `APT-009` completion/invoice reconciliation.
2. `BOOK-005`, `BOOK-011`, and `BOOK-012`.
3. Broader `AVAIL-001` working-hours boundary matrix.
4. Queue/concurrency and webhook concurrency where applicable.
5. Full `tests/Feature/QA` on fresh MySQL 8.4 + PHP 8.4 CI.
6. Final cross-surface reconciliation and production certification.

## Concurrency / certification gates still outstanding

- Same protected booking slot under concurrent requests (`CONC-001`).
- Duplicate concurrent booking submission (`CONC-002`).
- Queue call-next concurrency (`CONC-003`).
- Queue mutation race cases (`CONC-004`).
- Concurrent webhook delivery where applicable (`CONC-005`).
- Fresh Master QA MySQL CI success for the current `main` SHA.
- Final cross-surface reconciliation and production certification.

## Evidence interpretation

A test failure is recorded as a defect until root cause is addressed. A test passing on one run does not retroactively certify a strengthened scenario after a new assertion reveals a previously untested production behavior.

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
