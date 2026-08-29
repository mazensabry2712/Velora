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
NotificationDelivery (queued)
        |
        v
SendPublicAppointmentConfirmationEmail
        |
        +--> sending
        +--> sent
        +--> queued + retry on failure
        +--> failed after final attempt
```

## Delivery record

`notification_deliveries` lives in the tenant database and is keyed by a unique `dedupe_key` so the same event/channel/reference is not registered twice.

Tracked fields include event, channel, recipient, provider, status, attempts, last error and timestamps.

## Email

The public confirmation email uses scalar payload data only. The message contains the tenant name, customer name, service, staff, date, time, duration, queue number, public reference and canonical tracking URL.

The Mailable is not itself queued. The queue boundary is the `SendPublicAppointmentConfirmationEmail` job. This prevents double-queue behavior and keeps delivery status observable.

## Failure policy

A mail failure updates the delivery row with the error and rethrows so Laravel can retry the job. The job retries three times with a one-minute backoff. After the final failure, `failed()` marks the row as `failed`.

The public booking response remains successful even when notification dispatch or delivery fails.

## Idempotency

Email deliveries use:

```text
appointment.booked|email|<public_reference>
```

The delivery row is created with `firstOrCreate`. A sent delivery is not sent again by the job.

## Public security contract

Customer links must use the public reference only:

```text
/queue/status?ref=VL-XXXXXXXX
```

Do not put internal appointment, customer, staff or queue IDs in customer-facing links.

## WhatsApp extension

WhatsApp should use the same delivery model and event key pattern:

```text
appointment.booked|whatsapp|<public_reference>
```

The provider should be implemented behind a dedicated interface and must not be called from `PublicBookingController`. Provider failures must be retryable and must not fail the booking transaction.

## Future events

```text
appointment.booked
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
