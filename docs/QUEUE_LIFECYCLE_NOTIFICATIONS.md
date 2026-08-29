# Velora — Queue Lifecycle Notifications

## Purpose

This document defines the implementation contract for Queue Lifecycle Notifications. It extends the existing Queue module without changing the `/book` or `/queue/status` routes.

## Events

The lifecycle uses three business events:

- `queue.position_changed`
- `queue.almost_turn`
- `queue.turn_now`

The business event `QueueLifecycleNotificationRequested` is dispatched immediately when the queue state changes. Its delivery listener implements `ShouldHandleEventsAfterCommit`, so delivery persistence and external delivery begin only after a successful database transaction commit.

## Position semantics

The authoritative waiting order is the existing Queue business order:

```text
status = waiting
    ↓
VIP entries first (`is_vip = true`)
    ↓
ascending queue record id
```

Positions are one-based.

### `queue.turn_now`

Emitted when a queue entry changes:

```text
waiting -> serving
```

The served entry receives the `next` notification template.

### `queue.almost_turn`

Emitted when an existing waiting entry moves to position `1` because of a queue lifecycle change.

It uses the existing `queue_ready` localization catalog and is intentionally used instead of sending a second generic `queue.position_changed` message for the same transition.

### `queue.position_changed`

Emitted when an existing waiting entry moves from one waiting position to a different waiting position other than the special position-1 case.

It uses the existing `queue_position_update` localization catalog.

## Lifecycle triggers

The Queue observer evaluates ordering changes from:

- queue creation;
- queue status changes;
- VIP changes;
- queue-number changes;
- deletion of a waiting entry.

Newly inserted entries do not receive a synthetic position-change event. Only existing entries whose actual positions change are notified.

## Delivery contract

Every delivery is stored in the shared `notification_deliveries` ledger.

Core identity:

```text
appointment_id
public_reference
event
channel
recipient
provider
status
attempts
dedupe_key
metadata
```

Available channels in this lifecycle:

- `email`
- `whatsapp`

## Dedupe

For lifecycle events that represent a stable state milestone:

```text
queue.turn_now|channel|public_reference
queue.almost_turn|channel|public_reference
```

For position changes, the same appointment may legitimately move multiple times, so the exact event UUID participates in the identity:

```text
queue.position_changed|channel|public_reference|event_id
```

This prevents duplicate processing of the same event without blocking later legitimate movements.

## Queue job

`SendQueueLifecycleNotification` is the asynchronous delivery boundary.

It owns:

- attempt counting;
- `sending` transition;
- success → `sent`;
- retryable exception → `queued`;
- final worker failure → `failed`;
- honest WhatsApp `skipped` handling;
- tenant-context preservation.

The unconfigured WhatsApp provider never reports success. The existing `NullWhatsAppProvider` returns `skipped`, and the job preserves that state.

## Localization

No new notification translation schema is introduced. The existing catalog is reused:

```text
queue_next              -> queue.turn_now
queue_ready             -> queue.almost_turn
queue_position_update   -> queue.position_changed
```

All supported locales are protected by `SupportedLocaleCoreCoverageTest`, which requires identical notification keys and placeholder contracts across the supported locale catalogs.

## Legacy compatibility

`SendQueueNotification` and `QueueUpdateMail` remain unchanged in this milestone. The new lifecycle path is intentionally isolated so legacy behavior is not silently rewritten.

## Testing contract

`QueueLifecycleNotificationTest` covers:

- waiting → serving / `turn_now`;
- position 1 / `almost_turn`;
- ordinary position changes;
- VIP insertion shifts;
- delivery creation for email and WhatsApp;
- delivery idempotency;
- email delivery completion;
- unconfigured WhatsApp → `skipped`;
- final failed-job persistence.

## Non-goals

This milestone does not add:

- a new public API;
- changes to `/book`;
- changes to `/queue/status`;
- realtime WebSockets;
- a real WhatsApp provider integration;
- SMS delivery.

Those concerns can be introduced behind the same notification and delivery boundaries later.
