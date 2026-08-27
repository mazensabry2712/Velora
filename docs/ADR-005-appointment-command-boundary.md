# ADR-005 — Appointment Command Boundary

## Status
Accepted

Appointment persistence commands are exposed through `Domain\\Booking\\Contracts\\AppointmentCommand`. The initial implementation remains the existing Eloquent appointment repository to preserve behavior while allowing Application use cases to depend on a stable boundary.
