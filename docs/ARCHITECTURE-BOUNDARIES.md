# Velora Architecture Boundaries

The application is a modular monolith. New business workflows should enter through Application Actions, use Domain contracts for cross-layer capabilities, and resolve concrete integrations in Infrastructure.

Central and tenant persistence are separate contexts. Tenant-aware asynchronous work must restore tenant context before accessing tenant data.

Controllers are transport adapters and should not own multi-step business orchestration.
