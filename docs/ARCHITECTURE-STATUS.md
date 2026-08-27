# Architecture Status

The current main branch uses a modular monolith with Application use cases, Domain contracts, Infrastructure adapters, explicit central/tenant boundaries, and event-driven non-critical side effects. Legacy implementations remain only as compatibility adapters until regression coverage supports removal.