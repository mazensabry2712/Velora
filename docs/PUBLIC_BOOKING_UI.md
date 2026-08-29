# Velora Public Booking UI

## Goal

`/book` is the customer-facing booking experience. It is intentionally focused on selecting and confirming an appointment. Appointment confirmation and queue tracking are handled by `/queue/status`.

## Final customer flow

```text
/book
  ↓
1. Service
  ↓
2. Staff / Any available specialist
  ↓
3. Date + available time
  ↓
4. Customer details + review
  ↓
Confirm appointment
  ↓
POST /api/appointments
  ↓
public_reference
  ↓
/queue/status?ref=VL-XXXXXXXX
```

## What must NOT be shown on `/book`

The old inline confirmation surface is removed from the active customer experience:

- `successMessage`
- `Appointment Booked Successfully!`
- inline queue ticket
- inline confirmation card
- duplicate review script dependency
- old multi-section presentation where all booking fields are visible together

The booking response redirects the browser to the public tracking page after a successful creation.

## Booking layout

Desktop uses a two-column public surface:

```text
┌───────────────────────────────┬────────────────────┐
│ Booking step                  │ Your appointment   │
│                               │ summary             │
│ Service                       │ Service            │
│ Staff                         │ Staff              │
│ Date + time                   │ Date               │
│ Customer details              │ Time               │
│ Review                        │ Queue link         │
│ Confirm                       │                    │
└───────────────────────────────┴────────────────────┘
```

Mobile collapses to one column and keeps controls touch-friendly.

## Step behavior

Only the current step is visually active. The customer progresses through:

1. Service selection.
2. Staff selection, including `Any available specialist`.
3. Date selection and real availability lookup.
4. Customer details and final review.

Back navigation is available after the first step.

## Service selection

The server-provided `#service_id` remains the canonical form field and API value. The UI renders customer-friendly selection cards from its options.

## Staff selection

The server-provided `#staff_id` remains the canonical field. The UI adds:

```text
Any available specialist
```

Selecting this option queries availability across the eligible staff returned for the service. The selected slot resolves to a real staff ID before booking submission.

## Time selection

The native `#appointment_time` select remains as the canonical form field but is not the primary visual control. Available slots are rendered as touch-friendly buttons.

For `Any available specialist`, each slot includes the staff member that can provide it.

## Customer details

Customer fields are intentionally delayed until an actual time has been selected:

- Full name
- Phone number
- Email
- Optional notes

A review summary repeats the selected service, staff, date and time before the final confirmation action.

## Tenant branding

The header uses the current tenant branding:

- Tenant business name.
- Tenant logo when configured.
- Global `logo-bais.png` fallback when the tenant logo is missing or broken.
- Tenant-configured languages only.
- Tenant domain/host context.

Velora provides the design system; the tenant remains the public business identity.

## Theme

The booking surface supports:

- shared Velora light tokens.
- shared Velora dark tokens.
- dark-mode preference persistence.
- RTL/LTR document direction.
- reduced-motion support.

## CSS architecture

`public/css/velora-booking.css` is the canonical booking entry point.

Final layers:

```text
public/css/velora-booking.css
  ├── velora-booking-final.css
  ├── velora-booking-overrides.css
  ├── velora-booking-ui-alias.css
  └── velora-booking-layout-fixes.css
```

Legacy `v2/theme/wizard` files are no longer part of the canonical load chain and should not be reintroduced as additional overrides.

## JavaScript architecture

`public/js/dark-mode-booking.js` is the booking entry loader. Customer interaction behavior is implemented in `public/js/velora-booking-ui-polish.js`.

The booking script preserves the existing backend field and endpoint contract.

## Confirmation and queue tracking

The customer-facing ticket is owned by:

```text
/queue/status?ref=VL-XXXXXXXX
```

This page can show:

- appointment confirmation
- customer display name
- service
- staff
- date
- time
- duration
- queue number
- queue status
- people ahead when calculable

A raw queue number remains a legacy lookup and must not expose customer identity.

## Notifications

Email and WhatsApp are not considered implemented by the UI refactor. When implemented, they must use the same public reference:

```text
{tenant-host}/queue/status?ref={public_reference}
```

Notification delivery belongs in asynchronous application/infrastructure integrations and must not be required for the booking transaction to succeed.

## Browser QA

The browser suite must verify at minimum:

- tenant logo/branding
- no horizontal overflow
- desktop layout
- mobile layout
- language control
- service selection
- staff selection
- `Any available specialist`
- date/time selection
- customer details visibility after time selection
- review summary
- absence of inline confirmation surface
- redirect to public tracking after successful booking

## Canonical tests

```text
tests/Feature/PublicBookingSurfaceContractTest.php
tests/Feature/PublicBookingTest.php
tests/Feature/CustomerBookingJourneyTest.php
tests/Feature/PublicAppointmentReferenceTest.php
tests/browser/booking.spec.js
```
