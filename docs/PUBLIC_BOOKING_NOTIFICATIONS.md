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
        +--> Email confirmation job
        |      +--> sending
        |      +--> sent
        |      +--> queued + retry on failure
        |      +--> failed after final attempt
        |
        +--> WhatsApp confirmation job (when enabled)
               +--> sending
               +--> sent
               +--> skipped when provider is not configured
               +--> queued + retry on provider failure
               +--> failed after final attempt
```

## Delivery record

`notification_deliveries` lives in the tenant database and is keyed by a unique `dedupe_key` so the same event/channel/reference is not registered twice.

Tracked fields include event, channel, recipient, provider, status, attempts, last error and timestamps.

## Email confirmation

The public confirmation email uses scalar payload data only. The message contains the tenant name, customer name, service, staff, date, time, duration, queue number, public reference and canonical tracking URL.

The Mailable is render-only. `SendPublicAppointmentConfirmationEmail` owns the queue boundary and delivery state changes.

## Email failure policy

A mail failure updates the delivery row with the error and rethrows so Laravel can retry the job. The job retries three times with a one-minute backoff. After the final failure, `failed()` marks the row as `failed`.

The public booking response remains successful even when notification dispatch or delivery fails.

## WhatsApp confirmation

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

## Appointment reminders

The scheduled reminder command runs every 15 minutes and is a dispatcher only. It does not send external mail itself.

For active customer-email reminder rules, the command:

1. Finds eligible upcoming appointments inside a ±7 minute trigger window.
2. Resolves the new `Customer` relationship first, with the legacy `User` relationship as fallback.
3. Creates a `ReminderLog` for legacy/admin reporting compatibility.
4. Creates one `NotificationDelivery` using an event-specific idempotency key.
5. Dispatches `SendAppointmentReminderEmail`.

Canonical events currently are:

```text
1440 minutes -> appointment.reminder_24h
60 minutes   -> appointment.reminder_1h
other values -> appointment.reminder_<minutes>m
```

Email delivery keys are:

```text
appointment.reminder_24h|email|<public_reference>
appointment.reminder_1h|email|<public_reference>
```

The delivery job owns the external I/O and synchronizes the corresponding `ReminderLog` after success/final failure. This keeps retries independent from the scheduler and prevents duplicate reminder creation when the scheduler runs more than once.

The reminder email uses the same public tracking contract as booking confirmation:

```text
/queue/status?ref=VL-XXXXXXXX
```

No internal appointment, customer, staff or queue IDs are exposed in customer-facing links.

## Time and legacy compatibility

The reminder scanner supports both the current `starts_at` representation and legacy `date + time_slot` records. Cancelled, completed and no-show appointments are excluded.

The scheduler remains:

```text
reminders:process -> every 15 minutes
```

## Idempotency

Every notification channel gets its own `NotificationDelivery` record and its own unique dedupe key.

Examples:

```text
appointment.booked|email|<public_reference>
appointment.booked|whatsapp|<public_reference>
appointment.reminder_24h|email|<public_reference>
appointment.reminder_1h|email|<public_reference>
```

A delivery that already exists is not registered again. A sent delivery is not sent again by its job.

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

appointment.reminder_24h
  Email      implemented

appointment.reminder_1h
  Email      implemented
```

## Future events

```text
appointment.confirmed
appointment.rescheduled
appointment.cancelled
queue.position_changed
queue.almost_turn
queue.turn_now
appointment.completed
```

Each channel gets its own delivery record, allowing Email and WhatsApp to succeed or fail independently.
