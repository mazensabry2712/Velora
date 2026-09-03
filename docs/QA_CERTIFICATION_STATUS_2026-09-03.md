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

The canonical certification environment is MySQL 8.4 + PHP 8.4. The repository Master QA workflow runs `tests/Feature/QA` against MySQL 8.4.

## Verified local evidence — current session

| Area | Test suite | Result | Tests | Assertions | Evidence date |
|---|---|---:|---:|---:|---|
| Public booking journey | `CustomerBookingJourneyTest` | PASS | 5 | 41 | 2026-09-03 |
| Customer portal | `CustomerPortalJourneyTest` | PASS | 3 | 16 | 2026-09-03 |
| Appointment actions | `AppointmentActionsTest` | PASS | 13 | 37 | 2026-09-03 |
| Appointment/queue integration | `AppointmentQueueIntegrationTest` | PASS | 9 | 14 | 2026-09-03 |
| Booking rules | `BookingRulesScenarioTest` | PASS | 5 | 6 | 2026-09-03 |
| Booking availability rules | `BookingAvailabilityRulesScenarioTest` | PASS | 4 | 8 | 2026-09-03 |
| Appointment lifecycle baseline | `AppointmentLifecycleScenarioTest` | PASS | 3 | 11 | 2026-09-03 |

Current verified subtotal: **42 passed tests / 133 assertions**.

The latest observed local lifecycle command was:

```text
php -d memory_limit=512M artisan test tests/Feature/QA/AppointmentLifecycleScenarioTest.php --stop-on-failure
→ PASS — 3 passed (11 assertions) — 7.23s
```

These results are local MySQL-backed runs from the developer environment. They are not, by themselves, release certification.

## Current branch state

After the baseline lifecycle run, the lifecycle coverage was strengthened on `main`.

### Production fix committed

`47fbcf9ce72d7c4e10d3b73d3e7edd47eec74edd`

`ChangeAppointmentStatus` now:

- persists `confirmed_at`, `completed_at`, `cancelled_at`, or `no_show_at` with the status transition;
- treats `no_show` as a terminal queue state and moves its queue entry to `skipped`;
- returns the appointment with status history loaded.

### Regression coverage committed

`dce556b0f9b6b04ca4a6ce3a19fa6861818e1621`

`AppointmentLifecycleScenarioTest` now also verifies:

- completion creates the expected appointment invoice and completes its queue entry;
- cancellation records `cancelled_at` and skips the queue entry;
- no-show records `no_show_at` and skips the queue entry;
- each status transition produces a persisted status-history row.

**Execution status:** The strengthened lifecycle suite is **PENDING LOCAL EXECUTION**. The previous 3-test PASS was against the earlier baseline and does not certify the newly added cases.

## Booking/appointment gate status

### PASS — locally verified in the current session

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
- Appointment state-machine and queue synchronization baseline.
- Customer history/queue/invoice access.
- Appointment/queue integration lifecycle.

### PARTIAL / STILL REQUIRED

The focused suites still need dedicated proof for:

- `BOOK-005` invalid resource rejected.
- `BOOK-011` timezone conversion.
- `BOOK-012` notification failure must not corrupt booking.
- `AVAIL-001` complete working-hours matrix, not only the negative boundary.
- `APT-004` reschedule.
- strengthened `APT-006` no-show behavior after the latest production fix.
- strengthened `APT-008` persisted status-history completeness.
- strengthened `APT-009` completion/invoice consistency.

### Reschedule note

The current appointment domain status machine has no `rescheduled` status and the current application search did not reveal a dedicated reschedule application action. Documentation mentions rescheduling, but the runtime contract currently models only pending/confirmed/checked_in/in_service/completed/cancelled/no_show. Therefore `APT-004` is not being falsely marked PASS; it remains an explicit product/runtime gap until a real reschedule workflow is identified or implemented and tested.

## Findings created during the current pass

### QA-BOOK-002 — Holiday date comparison

Already fixed and regression-covered by `BookingAvailabilityRulesScenarioTest`.

### QA-TEST-BOOKRULES-001 — Booking rules fixture idempotency

Focused booking rules/availability fixtures were made safe for repeated execution.

### QA-BOOK-003 / related appointment identity regressions

Canonical `Customer`/`Staff` appointment identity is now used by the tested appointment flows.

### Current lifecycle finding

`QA-BOOK-003`-style lifecycle side-effect gap was identified and fixed as described above. The required next step is actual execution of the strengthened regression; no new PASS is claimed until the new suite is observed.

## Concurrency / certification gates still outstanding

- Same protected booking slot under concurrent requests.
- Queue call-next concurrency.
- Queue mutation race cases.
- Webhook concurrency where applicable.
- Fresh Master QA MySQL CI success for the current `main` SHA.
- Final cross-surface reconciliation and production certification.

## Working contract

Every meaningful defect/change in this pass must leave an audit trail containing scenario ID/area, observed failure, root cause, smallest correct fix, regression coverage, exact commit, observed local result, and CI status when available.

The Master QA runbook requires root-cause fixes rather than assertion weakening and requires documentation after meaningful QA changes.
