# Public Booking Reference & Customer Tracking Contract

## Purpose

Velora public booking uses one non-sequential public reference per online appointment. The reference is safe to place in customer-facing links and messages without exposing internal database IDs.

Example:

```text
VL-AB12CD34
```

## Customer journey

```text
/book
  ↓
Service
  ↓
Staff / Any available
  ↓
Date
  ↓
Available time
  ↓
Customer details
  ↓
POST /api/appointments
  ↓
Appointment + Queue + public_reference
  ↓
/queue/status?ref=VL-XXXXXXXX
  ↓
Live appointment + queue tracking
```

The same public reference is the identifier the customer uses after booking. The queue number remains a separate operational ticket for the current queue day.

## Identifier rules

`appointments.public_reference`:

- Prefix: `VL-`
- Length: 11 characters (`VL-` + 8 random uppercase alphanumeric characters)
- Generated automatically when an appointment is created if one is not supplied
- Collision-checked against existing and soft-deleted appointments in the tenant database
- Never derived from `id`, `customer_id`, or queue number

Internal IDs must not be exposed as the customer-facing lookup key.

## Public status lookup

Customer-facing page:

```text
GET /queue/status
```

Preferred query:

```text
/queue/status?ref=VL-XXXXXXXX
```

Legacy compatibility query:

```text
/queue/status?queue_number=A-027
```

The reference form is the canonical customer journey. Queue-number lookup remains available for existing public queue usage and is intentionally privacy-limited.

API endpoint:

```text
GET /api/queue/status/{identifier}
```

`{identifier}` may be either a public reference or a legacy queue number.

## Public response contract

For a valid public reference, the API returns only information required for the customer experience:

- Public reference
- Queue number
- Service name
- Staff display name
- Appointment date
- Appointment time
- Service duration
- Queue status
- Priority flag
- Number of people ahead when it can be calculated
- Customer display name

For a legacy queue-number lookup, customer identity fields remain excluded.

Customer private notes, internal notes, authentication secrets, payment secrets, and raw database identifiers are not part of the public contract.

## Tenant isolation

The route and API execute inside the current tenant domain context. Public reference lookup must resolve against the current tenant's appointment data. A reference belonging to another tenant must never return another tenant's appointment.

## Rate limiting

Public queue lookup remains rate-limited per tenant and client IP. A public reference does not bypass the protection.

## Confirmation experience

`/queue/status?ref=...` serves two roles:

1. Immediate appointment confirmation after a successful online booking.
2. Long-lived appointment and queue tracking from a saved link.

The booking page captures the `data.appointment.public_reference` value from the successful booking response and redirects to the status page. The status page then owns the confirmation, ticket, appointment details, and queue position display.

## Queue position

The public status payload exposes `people_ahead` for active waiting/serving queues when the position can be calculated from the current queue ordering.

An estimated wait duration is deliberately not part of the current contract. It should be introduced only after the queue-time calculation rules are defined and validated for VIP priority, service durations, breaks, and active service state.

## Notification integration

Email and WhatsApp are planned integrations and are **not** considered implemented solely by this contract.

When implemented, they should use the same public reference and link:

```text
{tenant-booking-host}/queue/status?ref={public_reference}
```

Delivery must be asynchronous and must never turn a successfully committed appointment into a failed booking solely because an external provider is unavailable.

Recommended notification events:

- Appointment confirmed
- Appointment moved/rescheduled
- Appointment cancelled
- Almost your turn
- Your turn
- Appointment completed

## Booking page behavior

The existing booking form keeps its current API and field contract. After a successful `POST /api/appointments`, the response contains the public reference under:

```text
 data.appointment.public_reference
```

The browser stores the reference for the current confirmation transition and redirects to:

```text
/queue/status?ref={public_reference}
```

This keeps `/book` focused on booking and `/queue/status` focused on the customer's ticket/tracking experience.

## Files

Core implementation:

```text
app/Models/Appointment.php
app/Http/Controllers/Tenant/PublicBookingController.php
app/Http/Controllers/Web/QueueController.php
resources/views/customer/booking.blade.php
resources/views/customer/queue-status.blade.php
public/js/dark-mode-booking.js
```

Schema:

```text
database/migrations/tenant/2026_08_29_000101_add_public_reference_to_appointments_table.php
```

Tests:

```text
tests/Feature/PublicAppointmentReferenceTest.php
tests/Feature/PublicBookingSurfaceContractTest.php
tests/Feature/PublicBookingTest.php
tests/Feature/CustomerBookingJourneyTest.php
```

## Future messaging integration

Email/WhatsApp providers should be implemented behind application-level notification contracts. The public reference is the only customer-facing appointment identifier required by those channels.

Provider-specific code belongs to infrastructure/integration layers and must not be embedded into booking domain logic.
