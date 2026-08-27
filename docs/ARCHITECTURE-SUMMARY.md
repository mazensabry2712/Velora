# Architecture Summary

Velora is being migrated incrementally to a modular monolith. The dependency direction is Interfaces -> Application -> Domain, with Infrastructure providing concrete persistence and external integrations. Central and tenant data remain explicit boundaries. New complex writes use Application Actions and transactions; non-critical side effects use events/jobs.
