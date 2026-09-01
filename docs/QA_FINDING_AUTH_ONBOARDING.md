# QA Finding — Onboarding Authorization

## Finding QA-AUTH-002

**Area:** Tenant onboarding authorization

**Issue:** The admin route group permits `Admin Tenant|Staff|Assistant`, and the onboarding write endpoints mutate tenant settings, create or update staff/service records, configure working hours, and enable booking. Without a method-level guard, Staff or Assistant could perform first-run administrative configuration.

**Fix:** `EnsureSubscriptionIsValid` now rejects `admin.onboarding.*` requests unless the authenticated user has the `Admin Tenant` role. The guard runs before subscription-state processing on the existing admin middleware stack, so no new route layer was introduced.

**Regression:** `AuthorizationMatrixExpandedScenarioTest::staff_and_assistant_cannot_mutate_onboarding()` verifies both Staff and Assistant receive HTTP 403 from onboarding step 1.

**Policy:** Read-only onboarding display remains available through the existing route authorization; only onboarding mutations are restricted.
