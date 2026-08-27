# Architecture Boundary Notes

Application Actions own orchestration. Domain contracts define capabilities. Infrastructure adapters own Eloquent, provider SDKs, framework integrations, and view composition. Controllers remain transport adapters only.
