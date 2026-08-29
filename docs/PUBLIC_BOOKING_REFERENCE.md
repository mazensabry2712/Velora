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
Choose service
  ↓
Choose specialist / Any available
  ↓
Choose date
  ↓
Choose available time
  ↓
Enter customer details
  ↓
Confirm appointment
  ↓
POST /api/appointments
  ↓
public_reference + queue
  ↓
/queue/status?ref=VL-XXXXXXXX
  ↓
Customer ticket + live queue tracking
```

`/book` is intentionally a booking-only surface. It does not render the final ticket or queue dashboard.

`/queue/status` is the reusable customer ticket and tracking surface.

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

Legacy compatibility query may still be accepted by the public API:

```text
/queue/status?queue_number=A-027
```

The reference form is the canonical customer journey.

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
- Estimated wait when queue duration rules permit it
- Customer display name
- Customer tracking URL

For a legacy queue-number lookup, customer identity fields remain excluded.

Customer private notes, internal notes, authentication secrets, payment secrets, and raw database identifiers are not part of the public contract.

## Tenant isolation

The route and API execute inside the current tenant domain context. Public reference lookup resolves only against the current tenant's appointment data. A reference belonging to another tenant must never return another tenant's appointment.

## Rate limiting

Public queue lookup remains rate-limited per tenant and client IP. A public reference does not bypass the protection.

## V3 UI contract

Booking implementation:

```text
resources/views/customer/booking.blade.php
public/css/velora-booking.css
public/js/velora-booking-v3.js
```

Queue implementation:

```text
resources/views/customer/queue-status.blade.php
public/css/velora-queue.css
public/js/velora-queue-v3.js
```

Both surfaces are tenant-branded, RTL/LTR aware, responsive, and independent from the global dark-mode enhancement stylesheet.

The booking surface keeps only the backend identifiers required by the booking contract:

```text
bookingForm
service_id
staff_id
appointment_date
appointment_time
notes
submitBtn
```

The public UI uses cards for service, specialist, date, and time selection instead of presenting the customer with a long legacy form.

## Confirmation experience

After a successful `POST /api/appointments`, the browser reads:

```text
data.appointment.public_reference
```

and redirects immediately to:

```text
/queue/status?ref={public_reference}
```

The old inline booking success panel is intentionally removed from `/book`.

## Queue position

The public status payload exposes `people_ahead` for active waiting/serving queues when the position can be calculated from the current queue ordering.

The current implementation also exposes `estimated_wait_minutes` when the number of people ahead and the configured service duration allow a deterministic estimate.

Future queue timing logic must account for VIP priority, breaks, service durations, active service state, and other operational rules before becoming a strict SLA/ETA.

## Notification integration

Email and WhatsApp are planned integrations and are **not** considered implemented solely by this contract.

When implemented, they should use the same public reference and tracking URL:

```text
{tenant-booking-host}/queue/status?ref={public_reference}
```

Delivery must be asynchronous and must never turn a successfully committed appointment into a failed booking solely because an external provider is unavailable.

Recommended notification events:

- Appointment confirmed
- Appointment reminder
- Appointment moved/rescheduled
- Appointment cancelled
- Almost your turn
- Your turn
- Appointment completed

## Files and verification

Core booking backend:

```text
app/Application/Booking/Actions/CreatePublicBooking.php
app/Application/Booking/DTOs/PublicBookingData.php
app/Http/Requests/Tenant/PublicBookingRequest.php
app/Http/Controllers/Tenant/PublicBookingController.php
```

Public queue backend:

```text
app/Http/Controllers/Web/QueueController.php
```

Schema:

```text
database/migrations/tenant/2026_08_29_000101_add_public_reference_to_appointments_table.php
```

Feature tests:

```text
tests/Feature/PublicAppointmentReferenceTest.php
tests/Feature/PublicBookingSurfaceContractTest.php
tests/Feature/PublicQueueSurfaceContractTest.php
tests/Feature/PublicBookingTest.php
tests/Feature/CustomerBookingJourneyTest.php
```

Browser tests:

```text
tests/browser/booking.spec.js
tests/browser/queue.spec.js
```
