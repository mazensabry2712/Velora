# Public Booking Notification Delivery

## Purpose

Public booking notifications are delivered after the appointment and queue transaction succeeds. Notification delivery must never decide whether the booking itself succeeds.

## Current flow

```text
POST /api/appointments
        |
        v
CreatePublicBooking transaction
        |
        +--> Appointment
        +--> Queue
        +--> public_reference
        |
        v
NotificationDelivery (per channel)
        |
        +--> Email job
        |      +--> sending
        |      +--> sent
        |      +--> queued + retry on failure
        |      +--> failed after final attempt
        |
        +--> WhatsApp job (when enabled)
               +--> sending
               +--> sent
               +--> skipped when provider is not configured
               +--> queued + retry on provider failure
               +--> failed after final attempt
```

## Delivery record

`notification_deliveries` lives in the tenant database and is keyed by a unique `dedupe_key` so the same event/channel/reference is not registered twice.

Tracked fields include event, channel, recipient, provider, status, attempts, last error and timestamps.

## Email

The public confirmation email uses scalar payload data only. The message contains the tenant name, customer name, service, staff, date, time, duration, queue number, public reference and canonical tracking URL.

The Mailable is rendered by `SendPublicAppointmentConfirmationEmail`. The queue boundary is the job, which updates the delivery row around the actual send operation.

## Email failure policy

A mail failure updates the delivery row with the error and rethrows so Laravel can retry the job. The job retries three times with a one-minute backoff. After the final failure, `failed()` marks the row as `failed`.

The public booking response remains successful even when notification dispatch or delivery fails.

## WhatsApp

WhatsApp uses the same delivery model and event key pattern:

```text
appointment.booked|whatsapp|<public_reference>
```

The application depends on `App\Domain\Notifications\Contracts\WhatsAppProvider` rather than a concrete provider. The default implementation is `NullWhatsAppProvider`, which returns `skipped` when no provider is configured. It intentionally never reports a false `sent` state.

The `SendPublicAppointmentConfirmationWhatsApp` job owns the retry/status lifecycle. A real provider can later be bound to the interface without changing the booking controller or public booking flow.

Enablement is controlled separately from the booking transaction through:

```text
services.whatsapp.enabled
```

## Idempotency

Email deliveries use:

```text
appointment.booked|email|<public_reference>
```

WhatsApp deliveries use:

```text
appointment.booked|whatsapp|<public_reference>
```

Each delivery is created with `firstOrCreate`. A sent delivery is not sent again by its job.

## Public security contract

Customer links must use the public reference only:

```text
/queue/status?ref=VL-XXXXXXXX
```

Do not put internal appointment, customer, staff or queue IDs in customer-facing links.

## Current notification contract

```text
appointment.booked
  Email      implemented
  WhatsApp   abstraction implemented; provider not configured by default
```

## Future events

```text
appointment.reminder
appointment.confirmed
appointment.rescheduled
appointment.cancelled
queue.position_changed
queue.almost_turn
queue.turn_now
appointment.completed
```

Each channel gets its own delivery record, allowing Email and WhatsApp to succeed or fail independently.
