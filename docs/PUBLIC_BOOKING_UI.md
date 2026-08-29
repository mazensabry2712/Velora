# Velora Public Booking + Queue V3

This document is the source of truth for the customer-facing booking experience and the public appointment tracking experience.

## 1. Public surfaces

### Booking

`/book`

Purpose: create a new appointment.

The page is intentionally focused on one job: help the customer select a service, choose a specialist, select a real available time, enter contact details, review the appointment, and confirm it.

It must not contain a final success ticket or queue-tracking dashboard.

### Tracking

`/queue/status`

Purpose: show an existing customer's appointment and live queue position.

Preferred access:

`/queue/status?ref=VL-XXXXXXXX`

The same public reference is the identifier sent later through customer notifications.

Legacy queue-number lookup remains supported by the API for compatibility but is privacy-limited.

## 2. Booking V3 flow

1. Service
2. Specialist
3. Date and time
4. Customer details
5. Confirmation by POST
6. Redirect to `/queue/status?ref=...`

The customer can choose `Any available specialist`. In that mode the browser checks the configured specialists for the selected service, shows available slots with the matching specialist name, and submits the actual selected `staff_id`.

## 3. Tenant branding

Both surfaces are tenant-branded.

The tenant logo is loaded from the tenant setting when configured. The global `logo-bais.png` is only the fallback when the tenant logo is unavailable.

The tenant controls which public languages appear through `available_languages`. The interface must never display the global list of languages when the tenant has configured a smaller set.

## 4. Booking markup contract

The booking page uses a clean V3 surface and a single canonical stylesheet/runtime:

- `resources/views/customer/booking.blade.php`
- `public/css/velora-booking.css`
- `public/js/velora-booking-v3.js`

Required backend form identifiers retained for compatibility:

- `bookingForm`
- `service_id`
- `staff_id`
- `appointment_date`
- `appointment_time`
- `notes`
- `submitBtn`

Public UI identifiers:

- `serviceCards`
- `staffCards`
- `dateChoices`
- `timeOptions`
- `summaryService`
- `summaryStaff`
- `summaryDate`
- `summaryTime`

Legacy `vb2-*`, `vb-final-*`, final/override/alias booking layers are not part of V3.

## 5. Queue markup contract

The queue page uses:

- `resources/views/customer/queue-status.blade.php`
- `public/css/velora-queue.css`
- `public/js/velora-queue-v3.js`

The page contains no inline styling or inline queue-fetch runtime.

The result view exposes only customer-safe appointment data returned by the public queue endpoint.

## 6. Public reference contract

Appointments receive an unguessable `VL-XXXXXXXX` reference.

The reference is not a database id and is not the queue number.

Example:

`VL-AB12CD34`

Queue number and public reference have different purposes:

- Public reference: customer-facing appointment lookup identifier.
- Queue number: operational position for the current queue.

A reference lookup is tenant-scoped and rate limited.

## 7. Public API

### Services

`GET /api/booking/services`

Returns only fields required to build the public booking UI.

### Staff

`GET /api/booking/staff/by-service/{service}`

Returns public staff identity without staff email or other private information.

### Availability

`GET /api/booking/available-timeslots`

Returns only currently available time slots for the selected tenant/service/staff/date context.

### Create appointment

`POST /api/appointments`

The response is sanitized. It exposes the public reference and queue information required for the next customer action, not the full internal appointment model.

### Queue status

`GET /api/queue/status/{identifier}`

The preferred identifier is the public appointment reference. The response includes queue number, status, people ahead, estimated wait where calculable, appointment details, and customer name only for a reference lookup.

## 8. Confirmation behavior

The booking page does not render the final confirmation ticket inline.

After a successful booking it redirects to:

`/queue/status?ref={public_reference}`

The queue-status surface becomes the reusable customer ticket and tracking destination.

## 9. Notifications integration point

Email and WhatsApp are not required to create an appointment successfully.

When notification delivery is added, the message must link to the same public tracking URL:

`/queue/status?ref={public_reference}`

Notification delivery should run asynchronously and must not roll back the appointment when an external provider fails.

Recommended customer events:

- Appointment confirmed
- Appointment reminder
- Almost your turn
- Your turn
- Appointment cancelled
- Appointment rescheduled

## 10. Responsive requirements

Desktop: booking form plus compact sticky summary.

Mobile: one-column flow with touch targets of at least 44px, no horizontal overflow, and date/time choices that can be selected without opening a large native form control.

Both surfaces support RTL and LTR.

## 11. Automated verification

Feature contracts:

- `tests/Feature/PublicBookingSurfaceContractTest.php`
- `tests/Feature/PublicAppointmentReferenceTest.php`
- `tests/Feature/PublicQueueSurfaceContractTest.php`
- `tests/Feature/PublicBookingTest.php`
- `tests/Feature/CustomerBookingJourneyTest.php`

Browser coverage is provided by Playwright under `tests/browser/`.

The browser tests must verify the actual V3 classes and must not bring back the legacy markup merely to satisfy a test.
