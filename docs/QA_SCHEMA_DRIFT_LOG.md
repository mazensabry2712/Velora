# Velora QA — Schema Drift / Regression Log

This file records production-path schema mismatches discovered by the Master QA suite and the exact regression protection added for them.

## 2026-09-01 — Booking schema drift

### Finding 1: `business_rules` contract mismatch

The booking engine calls `BusinessRule::getValue()` and `setValue()` using the canonical `key`, `value`, `type`, `description`, and `is_active` fields. The deployed tenant schema still required a legacy `name` field, causing a valid booking setup to fail with MySQL error 1364 (`Field 'name' doesn't have a default value`).

### Correction

Tenant migrations now:

- create `business_rules` with the canonical fields for fresh tenant databases
- add missing canonical columns when upgrading an older tenant schema
- make the legacy `name` column nullable when it exists
- backfill `key` from `name` where possible without deleting legacy data

### Regression protection

`MasterBusinessFlowScenarioTest::business_rule_schema_matches_the_contract_used_by_the_booking_engine()` exercises both the schema contract and the real model helpers.

---

## 2026-09-01 — Appointment status history mismatch

### Finding 2: history table name/schema drift

The `AppointmentStatusHistory` model declares `appointment_status_history`, while the Master QA assertion incorrectly referenced `appointment_status_histories`. The tenant schema also lacked the canonical singular table required by the model contract.

### Correction

A tenant migration now creates `appointment_status_history` with the model's required fields, appointment foreign key, indexes, and timestamps.

The Master QA assertion was corrected to use the canonical singular table name.

### Regression protection

`MasterBusinessFlowScenarioTest::appointment_status_history_schema_matches_the_model_contract()` verifies the table and required columns on every QA run.

---

## Rule

These findings reinforce the project QA policy:

```text
Production-path failure
    ↓
Reproduce with an executable test
    ↓
Minimal source/schema correction
    ↓
Permanent regression test
    ↓
CI verification
```

Do not weaken the assertion merely to make CI green. Do not add a package when a native schema/code correction is sufficient.
