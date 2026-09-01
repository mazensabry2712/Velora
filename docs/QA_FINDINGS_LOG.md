# Velora QA Findings Log

This log records defects discovered by the master QA program, the test that exposed each defect, the minimal fix, and the regression guard.

## Finding QA-BOOK-001 — Booking response returned HTTP 500

**Area:** Public booking

**Root cause:** The legacy web booking controller generated the customer queue tracking URL with a route parameter that the route does not define. The booking transaction itself could succeed, but response generation failed afterward.

**Fix:** Generate `/queue/status` with the public reference as a query parameter.

**Regression:** Golden booking scenario asserts the successful HTTP response and downstream records.

---

## Finding QA-SCHEMA-001 — business_rules schema drift

**Area:** Tenant booking rules

**Root causes discovered:**
- `BusinessRule` uses `key`, while older tenant schemas lacked it.
- Legacy `name` and `conditions` columns could remain `NOT NULL`, preventing inserts by the current model.

**Fix:** Tenant migration aligns the current `BusinessRule` contract and makes legacy blocking columns nullable without deleting legacy data.

**Regression:** The master booking scenario verifies the required columns and executes `BusinessRule::setValue()` followed by `getValue()`.

---

## Finding QA-SCHEMA-002 — appointment status history schema drift

**Area:** Appointment lifecycle

**Root cause:** The model writes to `appointment_status_history`, while the expected schema was not guaranteed in fresh tenant databases.

**Fix:** Added tenant migration for the canonical `appointment_status_history` table.

**Regression:** Master scenario verifies the table and all columns required by the model.

---

## Finding QA-QUEUE-001 — queue business date used created_at

**Area:** Queue / future appointments

**Root cause:** Queue readers and repository methods filtered daily data using `created_at` instead of the queue's explicit `queue_date`. A queue created today for a future appointment could therefore disappear from the future day's queue/status/dashboard views.

**Fix:** Queue readers and repository daily operations now use `queue_date`. Rows from older schemas where `queue_date` is null fall back to `created_at`.

**Affected operations:**
- Queue reader by date
- Customer queue lookup
- Public queue status
- Waiting-position calculation
- Estimated wait calculation
- Repository `getByDate()`
- Repository daily stats
- Repository `moveToNextDay()`

**Regression:** `QueueLifecycleScenarioTest::future_queue_is_read_by_business_date_not_creation_timestamp()` creates a future queue whose record was created today and verifies it is returned for the future business date.

---

## Test Policy

Every production defect discovered by Master QA must produce a regression test before the next feature family is accepted.

The test must validate the intended business outcome, not merely that an exception was thrown.

## Current Certification Rule

A feature family is not considered complete until:

1. Its happy path passes.
2. Its negative and edge cases pass.
3. Its data invariants pass.
4. Its dependent projections reconcile.
5. Its regression tests pass in MySQL CI.
