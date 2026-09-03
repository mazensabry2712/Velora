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
| Appointment lifecycle (strengthened) | `AppointmentLifecycleScenarioTest` | FAIL | 3 passed + 1 failed | 15 | 2026-09-03 |

Current verified passing subtotal before the latest defect: **42 passed tests / 133 assertions**.

Latest strengthened lifecycle execution exposed a real production defect:

```text
FAIL Tests\\Feature\\QA\\AppointmentLifecycleScenarioTest
✓ appointment status machine allows ...
✓ completed appointment moves its queue ...
✓ cancelled appointment moves its queue ...
⨯ no show moves its queue entry to skipped ...

Expected: no_show
Actual: cancelled
```

The failure occurred after `ChangeAppointmentStatus` correctly requested `no_show`; the subsequent queue update to `skipped` triggered the Queue model observer, which downgraded the appointment to `cancelled`.

## Findings added during this pass

### QA-APT-001 — Queue skip observer downgraded no-show appointments to cancelled

**Scenario:** `APT-006` no-show lifecycle.

**Observed:** The strengthened `AppointmentLifecycleScenarioTest` expected `appointment.status = no_show` after `ChangeAppointmentStatus::execute(..., no_show)`, but the persisted appointment became `cancelled`.

**Root cause:** `app/Models/Queue.php` treated every transition to `cancelled` or `skipped` as an instruction to set the linked appointment status to `cancelled`. `ChangeAppointmentStatus` first set the appointment to `no_show`, then set its queue row to `skipped`; the Queue observer then overwrote the terminal appointment state.

**Fix implemented:** The Queue observer now preserves `Appointment::STATUS_NO_SHOW` when the linked appointment is already `no_show`. Queue cleanup still produces `skipped`, while the appointment lifecycle remains `no_show`.

**Production commit:** `eabda6d8111fcc4c408294d55310431dcfc528bf`.

**Regression:** The strengthened `AppointmentLifecycleScenarioTest::no_show_moves_its_queue_entry_to_skipped_and_records_no_show_at()` remains the regression guard and must be re-run after pulling the fix.

## Previously verified local evidence

The following focused runs were completed successfully in the current development environment:

```text
CustomerBookingJourneyTest → 5 passed / 41 assertions
CustomerPortalJourneyTest → 3 passed / 16 assertions
AppointmentActionsTest → 13 passed / 37 assertions
AppointmentQueueIntegrationTest → 9 passed / 14 assertions
BookingRulesScenarioTest → 5 passed / 6 assertions / 7.56s
BookingAvailabilityRulesScenarioTest → 4 passed / 8 assertions / 7.33s
AppointmentLifecycleScenarioTest (before strengthened no-show regression) → 3 passed / 11 assertions / 7.23s
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
- Customer history/queue/invoice access.
- Appointment action and queue synchronization coverage.
- Appointment/queue integration lifecycle.

### OPEN / BLOCKED ON REGRESSION

- `APT-006` no-show — **production fix applied; re-run required**.
- `APT-008` persisted status-history completeness.
- `APT-009` completion/invoice consistency.
- `APT-004` reschedule.
- `BOOK-005` invalid resource rejected.
- `BOOK-011` timezone conversion.
- `BOOK-012` notification failure must not corrupt booking.
- `AVAIL-001` working-hours matrix beyond current negative-boundary coverage.

## Concurrency / certification gates still outstanding

- Same protected booking slot under concurrent requests.
- Queue call-next concurrency.
- Queue mutation race cases.
- Webhook concurrency where applicable.
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
