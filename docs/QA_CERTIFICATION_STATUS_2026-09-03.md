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

Current verified subtotal: **39 passed tests / 122 assertions**.

The two latest focused commands were:

```text
php -d memory_limit=512M artisan test tests/Feature/QA/BookingRulesScenarioTest.php --stop-on-failure
→ PASS — 5 passed (6 assertions) — 7.56s

php -d memory_limit=512M artisan test tests/Feature/QA/BookingAvailabilityRulesScenarioTest.php --stop-on-failure
→ PASS — 4 passed (8 assertions) — 7.33s
```

These results are local MySQL-backed runs from the developer environment. They are not, by themselves, release certification.

## Fixes made during this QA pass

### QA-SCHEMA-003 — Appointment `deposit_amount` schema drift

**Observed:** Public booking returned HTTP 500 because `BookingCreationService` wrote `appointments.deposit_amount` while the tenant migration did not create the column.

**Fix:** Added tenant migration `2026_09_03_000007_add_appointment_deposit_amount.php` with a decimal `deposit_amount` column and rollback support.

**Regression/evidence:** `CustomerBookingJourneyTest` subsequently passed all 5 tests / 41 assertions.

### QA-TEST-BOOK-001 — Customer booking working-hours fixture was not idempotent

**Observed:** Repeated tests targeting the same staff/day violated the unique `staff_working_hours` constraint.

**Fix:** `CustomerBookingJourneyTest` now uses `updateOrCreate()` for the controlled staff/day fixture.

**Regression/evidence:** `CustomerBookingJourneyTest` subsequently passed all 5 tests / 41 assertions.

### QA-TEST-BOOK-002 — Customer booking slot leaked between tests

**Observed:** A later independent test received HTTP 409 because a previous test's appointment remained visible to slot conflict detection.

**Fix:** The journey fixture clears only appointments for its controlled staff/date slot before preparing the fixture. Production double-booking logic remains unchanged.

**Regression/evidence:** `CustomerBookingJourneyTest` subsequently passed all 5 tests / 41 assertions.

### QA-TEST-PORTAL-001 — Portal fixture duplicated the canonical customer email

**Observed:** `CustomerPortalJourneyTest` attempted to insert `customer@test.com` although the tenant test base already owns that unique email.

**Fix:** Portal tests reuse the canonical customer identity fixture instead of creating a duplicate record.

**Regression/evidence:** `CustomerPortalJourneyTest` passed all 3 tests / 16 assertions.

### QA-TEST-IDENTITY-002 — Appointment action test still used legacy staff identity fields

**Observed:** `AppointmentActionsTest` posted a User ID to a request that validates against `staff.id`, returning a 422.

**Fix:** Test fixtures and assertions were aligned with canonical `staff_id_new` / `Staff` identity.

**Regression/evidence:** `AppointmentActionsTest` progressed to its next real defect and ultimately passed 13 tests / 37 assertions after the follow-up fixes.

### QA-BOOK-003 — Customer portal reader selected a non-existent `staff.name` column

**Observed:** `EloquentAppointmentReader::forCustomer()` eager-loaded `staff:id,name`, but `staff` stores `first_name` and `last_name`; `name` is an accessor.

**Fix:** Reader now selects real staff columns (`id, first_name, last_name`) while retaining the accessor-generated display name.

**Regression/evidence:** `CustomerPortalJourneyTest` passed all 3 tests / 16 assertions.

### QA-TEST-APT-001 — Appointment queue fixture omitted canonical start time

**Observed:** `AddAppointmentToQueue` correctly rejected an appointment without `starts_at`, returning `Appointment start time is missing.`

**Fix:** `AppointmentActionsTest` fixture now supplies `starts_at`, `ends_at`, `ends_at_with_buffer`, timezone, price and source.

**Regression/evidence:** `AppointmentActionsTest` passed 13 tests / 37 assertions.

### QA-TEST-QUEUE-001 — VIP queue fixture updated the wrong entity

**Observed:** Test changed `User::is_vip`, while queue derivation uses the canonical `Customer` business state.

**Fix:** Test now sets the canonical `Customer::ltv_tier = vip` state.

**Regression/evidence:** `AppointmentActionsTest` passed 13 tests / 37 assertions.

### QA-TEST-BOOKRULES-001 — Booking rules QA fixtures were not idempotent

**Observed:** Re-running the focused booking-rules scenario classes against the shared tenant fixture could collide with the unique `staff_working_hours` constraint for the same staff/day.

**Root cause:** The scenarios created the same controlled staff/day working-hours row on every method without first reconciling an existing fixture.

**Fix:** The focused booking rules/availability fixture setup was made idempotent for repeated class execution while preserving the production working-hours contract.

**Regression/evidence:**
- `BookingRulesScenarioTest` → **5 passed / 6 assertions / 7.56s**
- `BookingAvailabilityRulesScenarioTest` → **4 passed / 8 assertions / 7.33s**

### QA-TEST-APT-002 — Appointment lifecycle QA fixture used legacy identity contract

**Observed:** `AppointmentLifecycleScenarioTest` created appointments using legacy `customer_id`, `staff_id`, `date`, and `time_slot` fields, while the current runtime contract uses `customer_id_new`, `staff_id_new`, and canonical appointment timestamps.

**Root cause:** The lifecycle regression test had not yet been migrated with the broader appointment identity cleanup.

**Fix:** Lifecycle fixtures now create appointments through `customer_id_new` / `staff_id_new`, populate `starts_at`, `ends_at`, `ends_at_with_buffer`, timezone and source, and assert the canonical relationships after state transitions. Production appointment status behavior was not changed by this test-only fix.

**Commit:** `07c84e60612c131bfab4535447a1bcadf03dce18`.

**Status:** Test fix is committed. Focused `AppointmentLifecycleScenarioTest` execution is required next; no PASS is claimed yet.

## Current booking/appointment gate status

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
- Customer history/queue/invoice access.
- Appointment action and queue synchronization coverage.
- Appointment/queue integration lifecycle.

### PARTIAL / STILL REQUIRED

The current focused suites do **not yet prove every catalog item end-to-end**. In particular:

- `BOOK-005` invalid resource rejected.
- `BOOK-011` timezone conversion.
- `BOOK-012` notification failure must not corrupt booking.
- `AVAIL-001` working-hours matrix beyond the currently covered negative boundary.

These remain open until a dedicated scenario or equivalent existing regression proves the business outcome and downstream invariants.

## Next lifecycle gates

- `APT-001` full appointment lifecycle.
- `APT-002` confirm.
- `APT-003` cancel.
- `APT-004` reschedule.
- `APT-005` complete.
- `APT-006` no-show.
- `APT-007` invalid transition.
- `APT-008` status history.
- `APT-009` completion/invoice consistency.

## Concurrency / certification gates still outstanding

- Same protected booking slot under concurrent requests.
- Queue call-next concurrency.
- Queue mutation race cases.
- Webhook concurrency where applicable.
- Fresh Master QA MySQL CI success for the current `main` SHA.
- Final cross-surface reconciliation and production certification.

## Important evidence interpretation

The local results above prove the exact commands executed in the current development environment. They do not prove that the current `main` SHA has a successful GitHub Actions run unless such a run is explicitly observed and recorded. The Master QA workflow is configured for MySQL 8.4 + PHP 8.4 and runs the complete `tests/Feature/QA` suite.

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

The Master QA runbook requires root-cause fixes rather than assertion weakening, and requires documentation after meaningful QA changes.
