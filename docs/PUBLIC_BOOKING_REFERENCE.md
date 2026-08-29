# Public Booking Reference & Customer Tracking Contract

## Purpose

Velora public booking uses one non-sequential public reference per online appointment. The reference is designed to be safe to place in customer-facing links and messages without exposing internal database IDs.

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
```

The same public reference is the identifier the customer can use after booking. The queue number remains a separate operational ticket for the current queue day.

## Identifier rules

`appointments.public_reference`:

- Prefix: `VL-`
- Length: 11 characters in the current implementation (`VL-` + 8 random uppercase alphanumeric characters)
- Non-sequential and generated automatically on appointment creation
- Unique in the tenant database, including soft-deleted appointments
- Never derived from `id`, `customer_id`, or queue number

Internal IDs must not be exposed as the customer-facing lookup key.

## Public status lookup

Customer-facing page:

```text
GET /queue/status
```

Supported query forms:

```text
/queue/status?ref=VL-XXXXXXXX
/queue/status?queue_number=A-027
```

The reference form is the preferred customer journey. Legacy queue-number lookup remains available for existing public queue usage.

API endpoint:

```text
GET /api/queue/status/{identifier}
```

`{identifier}` may be either a public reference or a legacy queue number.

## Data returned to the public customer page

For a valid reference, the public response includes only booking/queue information necessary for the customer experience:

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

Customer private notes, authentication secrets, internal notes, and raw database credentials are not part of the public contract.

## Tenant isolation

The route is executed inside the tenant domain context. Public reference lookup must resolve against the current tenant's appointment data. A reference from another tenant must not return another tenant's appointment.

## Rate limiting

Public queue lookup remains rate-limited per tenant and client IP. The public reference does not bypass this protection.

## Confirmation experience

`/queue/status?ref=...` serves two roles:

1. Confirmation page immediately after booking.
2. Long-lived appointment/queue tracking page opened later from Email, WhatsApp, QR code, or a copied link.

The page switches to a confirmation presentation when `ref` is supplied, while keeping the same live queue tracking surface.

## Notification integration

Email and WhatsApp notifications should use the same public reference and link:

```text
{tenant-booking-host}/queue/status?ref={public_reference}
```

Notification delivery must be asynchronous and must never turn a successful appointment transaction into a failed booking solely because an external provider is unavailable.

Recommended notification events:

- Appointment confirmed
- Appointment moved/rescheduled
- Appointment cancelled
- Almost your turn
- Your turn
- Appointment completed

## Booking page behavior

The browser booking flow captures `data.appointment.public_reference` from the successful `/api/appointments` response and redirects the customer to:

```text
/queue/status?ref={public_reference}
```

This keeps the booking form focused on making the appointment while the status page owns confirmation, ticket details, and queue tracking.

## Files

Core implementation:

```text
app/Models/Appointment.php
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

The Email/WhatsApp providers should be implemented behind application-level notification contracts. The public reference is the only customer-facing appointment identifier required by those channels.

Provider-specific code belongs to infrastructure/integration layers and must not be embedded into booking domain logic.
