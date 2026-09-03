# Velora QA Findings Addendum — 2026-09-03

## QA-CONC-001 / QA-CONC-002 — Windows concurrency harness limitation

The same-slot and duplicate-booking race suites launch independent PHP workers. In the user's Windows/Herd environment, Symfony Process children exited before reaching the synchronization barrier, while direct CLI invocation of the worker file succeeded. The race assertions were therefore not relaxed.

**Classification:** test-environment limitation, not a production defect.

**Policy:** `CONC-001` and `CONC-002` are skipped on Windows and remain enforced in the Ubuntu + MySQL 8.4 Master QA workflow.

## QA-QUEUE-001 — Stale queue transition race

**Area:** Queue status mutation.

**Observed risk:** `TransitionQueueEntry` validated the status transition against the caller's in-memory `Queue` model and then updated the row without a transaction or row lock. A concurrent actor could change the persisted status after the model was read, allowing a stale transition to overwrite newer state.

**Minimal production fix:** `TransitionQueueEntry` now executes inside a DB transaction, reloads the queue row with `lockForUpdate()`, validates against the locked current status, and only then persists the transition.

**Production commit:** `60e6497ef7218a59283123abcf153c52e41f0261`.

**Regression:** `QueueLifecycleScenarioTest::stale_queue_model_cannot_overwrite_a_newer_terminal_transition()` creates a stale model, commits a newer terminal transition, then verifies the stale transition is rejected and the terminal state remains intact.

**Regression commit:** `dbf711bd4166a2e4ccd700d2a2f7bf5fc4bd9ede`.

**Current state:** User-run required for focused regression. Fresh MySQL CI required for certification.
