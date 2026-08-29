# Public Booking Notifications

## Scope

This document defines customer-facing notifications emitted after a public appointment is successfully committed.

## Canonical flow

```text
POST /api/appointments
        ↓
CreatePublicBooking transaction commits
        ↓
PublicAppointmentConfirmationMail queued
        ↓
Customer opens tracking_url
        ↓
/queue/status?ref=VL-XXXXXXXX
```

The notification layer must never make a committed booking appear failed when an external notification provider is unavailable.

## Booking confirmation email

The canonical email is `PublicAppointmentConfirmationMail` and implements Laravel's `ShouldQueue` contract.

It receives only scalar customer-facing data:

- tenant name
- customer name
- service name
- specialist name
- appointment date/time
- duration
- queue number
- public reference
- tracking URL
- locale

This avoids serializing tenant-bound Eloquent models into the asynchronous job.

The tracking button must point to the tenant booking host:

```text
/queue/status?ref={public_reference}
```

The email must not expose internal appointment, customer, staff, tenant, payment, or database identifiers.

## Failure policy

Email queueing is attempted only after `CreatePublicBooking` returns successfully, which means the booking transaction has already committed.

If queueing the email fails, the exception is logged with the tenant key and public reference and the booking response remains successful.

## Localization

Notification copy is resolved using the locale active for the public booking request. English and Arabic copy are maintained independently in:

```text
lang/en/public_booking.php
lang/ar/public_booking.php
```

Future tenant-supported languages should follow the same keys.

## WhatsApp

WhatsApp is intentionally not coupled to the booking controller yet. The next implementation stage should add a provider interface and queue-backed channel using the same scalar notification payload and tracking URL.

A provider outage must follow the same failure policy as email: the appointment remains committed and the failure is observable through logs/notification status, not surfaced as a booking failure.

## Planned lifecycle events

```text
appointment.booked
appointment.reminder
appointment.rescheduled
appointment.cancelled
queue.position_changed
queue.almost_turn
queue.turn_now
appointment.completed
```

The first production event is `appointment.booked`. Queue alerts and reminders should be added only after their operational triggers are defined.

## Testing

Feature coverage must verify:

1. A successful public booking creates the appointment and queue.
2. A confirmation email is queued with the generated public reference.
3. The tracking URL contains the public reference and tenant queue path.
4. Notification failures do not turn a committed booking into an API failure.
5. No internal IDs are included in the public notification payload.

## Related contracts

See `docs/PUBLIC_BOOKING_REFERENCE.md` for the public reference and tracking contract.
