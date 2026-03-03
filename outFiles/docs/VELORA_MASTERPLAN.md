# VELORA — Global Booking SaaS — Master Architecture Plan

> **Platform:** Velora — Multi-Tenant Booking SaaS  
> **Architect:** Senior SaaS / Laravel 11 Expert  
> **Scale Target:** 100,000+ tenants · Millions of bookings/month  
> **Competitors:** Fresha · Mindbody · Booksy  
> **Date:** March 2, 2026

---

## Table of Contents

1. [System Architecture Overview](#system-architecture-overview)
2. [Phase 1 — Core Booking Engine](#phase-1--core-booking-engine)
3. [Phase 2 — Payments & Billing](#phase-2--payments--billing)
4. [Phase 3 — Automation & Smart Logic](#phase-3--automation--smart-logic)
5. [Phase 4 — Analytics & Reporting](#phase-4--analytics--reporting)
6. [Phase 5 — Enterprise & Scale](#phase-5--enterprise--scale)
7. [Phase 6 — Marketplace](#phase-6--marketplace)
8. [Phase 7 — Mobile Layer](#phase-7--mobile-layer)
9. [Full ERD & Database Schema](#full-erd--database-schema)
10. [Testing Strategy](#testing-strategy)
11. [Implementation Roadmap](#implementation-roadmap)
12. [Scaling Considerations](#scaling-considerations)

---

## System Architecture Overview

### Guiding Principles

| Principle | Application |
|-----------|-------------|
| **Domain-Driven Design** | Each domain (Booking, Payment, Staff, Notification) lives in its own bounded context |
| **Multi-Tenant Isolation** | All tenant data lives in isolated databases via Stancl/Tenancy |
| **API-First** | Every feature has a RESTful API — Blade views are consumers, not owners |
| **Driver-Based Integrations** | Payments, SMS, Push, Email are abstracted behind interfaces |
| **Event-Driven Core** | Booking state changes emit Events → Listeners handle side effects |
| **Queue-Everything** | No heavy computation in the request cycle |
| **Zero Hardcoded Text** | All strings route through `__()` |
| **GDPR-by-Default** | Consent, audit logs, data export built in from day 1 |

### Folder Structure (Domain-Oriented)

```
app/
├── Domain/
│   ├── Booking/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   ├── Events/
│   │   ├── Listeners/
│   │   ├── DTOs/
│   │   └── Policies/
│   ├── Staff/
│   ├── Payment/
│   ├── Notification/
│   ├── Analytics/
│   ├── Marketplace/
│   └── Tenant/
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/
│   │   └── Web/
│   ├── Requests/
│   ├── Resources/
│   └── Middleware/
├── Infrastructure/
│   ├── Payment/
│   │   ├── Contracts/PaymentGatewayInterface.php
│   │   ├── Drivers/StripeDriver.php
│   │   └── Drivers/PaymobDriver.php
│   ├── Sms/
│   │   ├── Contracts/SmsProviderInterface.php
│   │   ├── Drivers/TwilioDriver.php
│   │   └── Drivers/VonageDriver.php
│   └── Push/
│       ├── Contracts/PushProviderInterface.php
│       └── Drivers/FirebaseDriver.php
└── Support/
    ├── Traits/
    ├── Helpers/
    └── Macros/
```

### Tenant vs Central Architecture

```
Central DB (velora.test)
├── tenants              ← Tenant registry
├── domains              ← Custom domains per tenant
├── subscription_plans
├── tenant_subscriptions
├── super_admin_users
├── marketplace_listings ← Discoverable via public directory
└── platform_analytics  ← Cross-tenant aggregated (anonymized)

Tenant DB ({subdomain}.velora.test)
├── Booking domain tables
├── Staff / Resource tables
├── Payment tables
├── Customer tables
├── Analytics tables
└── Notification tables
```

---

## Phase 1 — Core Booking Engine

### 1.1 Services System

#### Architecture Overview
Services are the atomic unit of the booking system. Each service belongs to a category, can be assigned to multiple staff, requires specific resources (rooms/chairs/devices), and has its own pricing, duration, and buffer time logic.

#### Database Schema

```sql
-- Service Categories
CREATE TABLE service_categories (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          JSON NOT NULL,          -- {"en": "Hair", "ar": "شعر", ...}
    slug          VARCHAR(100) NOT NULL,
    icon          VARCHAR(100),
    color         VARCHAR(7),
    sort_order    SMALLINT DEFAULT 0,
    is_active     BOOLEAN DEFAULT TRUE,
    created_at    TIMESTAMP,
    updated_at    TIMESTAMP,
    INDEX idx_active (is_active)
);

-- Services
CREATE TABLE services (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id           BIGINT UNSIGNED NOT NULL,
    name                  JSON NOT NULL,     -- {"en": "Haircut", "ar": "قص شعر"}
    description           JSON,
    slug                  VARCHAR(150) NOT NULL,
    duration_minutes      SMALLINT NOT NULL DEFAULT 30,
    buffer_before_minutes SMALLINT NOT NULL DEFAULT 0,
    buffer_after_minutes  SMALLINT NOT NULL DEFAULT 0,
    price                 DECIMAL(10,2) NOT NULL,
    deposit_amount        DECIMAL(10,2) DEFAULT 0,
    deposit_pct           TINYINT DEFAULT 0,  -- 0 = fixed, 1-100 = percent
    max_capacity          TINYINT DEFAULT 1,  -- for group sessions
    is_group              BOOLEAN DEFAULT FALSE,
    is_active             BOOLEAN DEFAULT TRUE,
    is_online_bookable    BOOLEAN DEFAULT TRUE,
    image                 VARCHAR(255),
    sort_order            SMALLINT DEFAULT 0,
    metadata              JSON,
    created_at            TIMESTAMP,
    updated_at            TIMESTAMP,
    deleted_at            TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES service_categories(id),
    INDEX idx_category_active (category_id, is_active),
    INDEX idx_bookable (is_online_bookable, is_active)
);

-- Resources (rooms, chairs, devices)
CREATE TABLE resources (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        JSON NOT NULL,
    type        ENUM('room','chair','equipment','other') NOT NULL,
    quantity    TINYINT NOT NULL DEFAULT 1,
    is_active   BOOLEAN DEFAULT TRUE,
    metadata    JSON,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP
);

-- Service ↔ Resource (which services need which resources)
CREATE TABLE service_resources (
    service_id  BIGINT UNSIGNED NOT NULL,
    resource_id BIGINT UNSIGNED NOT NULL,
    quantity    TINYINT NOT NULL DEFAULT 1,
    PRIMARY KEY (service_id, resource_id)
);
```

#### Key Model

```php
// app/Domain/Booking/Models/Service.php
class Service extends Model
{
    use SoftDeletes, HasTranslations;

    protected $translatable = ['name', 'description'];

    protected $casts = [
        'price'          => Money::class,  // custom cast
        'is_active'      => 'boolean',
        'is_group'       => 'boolean',
        'metadata'       => 'array',
    ];

    public function getTotalDurationAttribute(): int
    {
        return $this->buffer_before_minutes
             + $this->duration_minutes
             + $this->buffer_after_minutes;
    }

    public function getDepositAmountForPrice(float $price): float
    {
        if ($this->deposit_pct > 0) {
            return round($price * ($this->deposit_pct / 100), 2);
        }
        return $this->deposit_amount;
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'staff_services')
                    ->withPivot('override_price', 'override_duration');
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'service_resources')
                    ->withPivot('quantity');
    }

    public function scopeOnlineBookable(Builder $q): Builder
    {
        return $q->where('is_online_bookable', true)->where('is_active', true);
    }
}
```

---

### 1.2 Staff System

#### Database Schema

```sql
-- Staff
CREATE TABLE staff (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED,        -- can be null (non-login staff)
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    email           VARCHAR(180),
    phone           VARCHAR(30),
    avatar          VARCHAR(255),
    title           JSON,                   -- {"en": "Senior Stylist"}
    bio             JSON,
    timezone        VARCHAR(50) NOT NULL DEFAULT 'UTC',
    commission_type ENUM('none','fixed','percent') DEFAULT 'none',
    commission_value DECIMAL(8,2) DEFAULT 0,
    is_active       BOOLEAN DEFAULT TRUE,
    accepts_bookings BOOLEAN DEFAULT TRUE,
    sort_order      SMALLINT DEFAULT 0,
    color           VARCHAR(7),             -- calendar display color
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    deleted_at      TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_active_bookings (is_active, accepts_bookings)
);

-- Staff ↔ Services (many-to-many with overrides)
CREATE TABLE staff_services (
    staff_id          BIGINT UNSIGNED NOT NULL,
    service_id        BIGINT UNSIGNED NOT NULL,
    override_price    DECIMAL(10,2),
    override_duration SMALLINT,
    PRIMARY KEY (staff_id, service_id)
);

-- Staff Working Hours
CREATE TABLE staff_working_hours (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id    BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT NOT NULL,           -- 0=Sun, 6=Sat
    start_time  TIME NOT NULL,
    end_time    TIME NOT NULL,
    is_working  BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    UNIQUE KEY uq_staff_day (staff_id, day_of_week)
);

-- Staff Breaks (within a working day)
CREATE TABLE staff_breaks (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id    BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT NOT NULL,
    start_time  TIME NOT NULL,
    end_time    TIME NOT NULL,
    label       VARCHAR(100),
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
);

-- Staff Time-Off (specific dates)
CREATE TABLE staff_time_off (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id     BIGINT UNSIGNED NOT NULL,
    start_date   DATE NOT NULL,
    end_date     DATE NOT NULL,
    reason       VARCHAR(255),
    all_day      BOOLEAN DEFAULT TRUE,
    start_time   TIME,
    end_time     TIME,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    INDEX idx_staff_dates (staff_id, start_date, end_date)
);

-- Business Holidays
CREATE TABLE holidays (
    id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date  DATE NOT NULL,
    name  JSON,
    UNIQUE KEY uq_date (date)
);
```

---

### 1.3 Smart Time Slot Engine

#### Architecture Overview

The slot engine is the most critical component. It must be:
- **Deterministic:** Same inputs always produce same outputs
- **Conflict-free:** Multi-layer conflict detection (staff + resource + capacity)
- **Timezone-aware:** Store all times in UTC, display in tenant/user timezone
- **Cacheable:** Slot availability is computed and cached per staff per day
- **Invalidatable:** Cache invalidated when booking is made/changed

#### Core Service Class

```php
// app/Domain/Booking/Services/SlotEngine.php

class SlotEngine
{
    public function __construct(
        private BookingRepository   $bookings,
        private StaffRepository     $staff,
        private ResourceRepository  $resources,
        private Cache               $cache,
    ) {}

    /**
     * Get available slots for a service + staff on a given date.
     * Returns array of Carbon instances in tenant's timezone.
     */
    public function getAvailableSlots(
        Service $service,
        Staff   $staff,
        Carbon  $date,
        string  $timezone
    ): Collection {
        $cacheKey = "slots:{$staff->id}:{$service->id}:{$date->toDateString()}";

        return $this->cache->remember($cacheKey, 300, function () use (
            $service, $staff, $date, $timezone
        ) {
            // 1. Check if date is a holiday
            if ($this->isHoliday($date)) return collect();

            // 2. Get staff working window for that day
            $window = $this->getWorkingWindow($staff, $date, $timezone);
            if (!$window) return collect();

            // 3. Get all unavailable blocks
            $busyBlocks = $this->getBusyBlocks($staff, $date, $timezone);

            // 4. Generate candidate slots (every N minutes based on service slot_interval config)
            $slotInterval = config('booking.slot_interval_minutes', 15);
            $slots = $this->generateCandidateSlots($window, $slotInterval);

            // 5. Filter out slots that conflict
            $serviceDuration = $service->total_duration; // includes buffers
            return $slots->filter(function (Carbon $slot) use ($serviceDuration, $busyBlocks) {
                $end = $slot->copy()->addMinutes($serviceDuration);
                return !$this->conflictsWithAny($slot, $end, $busyBlocks);
            });
        });
    }

    /**
     * Validate a specific slot before booking — always check live, not cached.
     * Called inside a DB transaction to prevent race conditions.
     */
    public function validateSlot(
        Service $service,
        Staff   $staff,
        Carbon  $startsAt,
        ?int    $excludeBookingId = null
    ): SlotValidationResult {
        $endsAt = $startsAt->copy()->addMinutes($service->total_duration);

        // 1. Staff working hours
        if (!$this->isWithinWorkingHours($staff, $startsAt, $endsAt)) {
            return SlotValidationResult::fail('staff_not_working');
        }

        // 2. Holiday check
        if ($this->isHoliday($startsAt)) {
            return SlotValidationResult::fail('holiday');
        }

        // 3. Staff time-off
        if ($this->staffOnTimeOff($staff, $startsAt, $endsAt)) {
            return SlotValidationResult::fail('staff_unavailable');
        }

        // 4. Booking conflict (with DB lock for race condition safety)
        $conflicting = $this->bookings->getConflictingBookings(
            $staff->id, $startsAt, $endsAt, $excludeBookingId
        );
        if ($conflicting->isNotEmpty()) {
            return SlotValidationResult::fail('slot_taken');
        }

        // 5. Resource availability
        foreach ($service->resources as $resource) {
            if (!$this->isResourceAvailable($resource, $startsAt, $endsAt)) {
                return SlotValidationResult::fail('resource_unavailable');
            }
        }

        return SlotValidationResult::ok();
    }

    private function conflictsWithAny(Carbon $start, Carbon $end, Collection $blocks): bool
    {
        return $blocks->contains(function ($block) use ($start, $end) {
            // Overlap: start < block.end AND end > block.start
            return $start->lt($block['end']) && $end->gt($block['start']);
        });
    }

    private function getBusyBlocks(Staff $staff, Carbon $date, string $timezone): Collection
    {
        $blocks = collect();

        // Confirmed + Pending bookings
        $bookings = $this->bookings->getForStaffOnDate($staff->id, $date);
        foreach ($bookings as $booking) {
            $blocks->push([
                'start' => Carbon::parse($booking->starts_at)->setTimezone($timezone),
                'end'   => Carbon::parse($booking->ends_at_with_buffer)->setTimezone($timezone),
                'type'  => 'booking',
            ]);
        }

        // Staff breaks
        foreach ($staff->breaksForDay($date->dayOfWeek) as $break) {
            $blocks->push([
                'start' => Carbon::parse($date->toDateString().' '.$break->start_time)->setTimezone($timezone),
                'end'   => Carbon::parse($date->toDateString().' '.$break->end_time)->setTimezone($timezone),
                'type'  => 'break',
            ]);
        }

        return $blocks;
    }
}
```

#### Race Condition Prevention (Pessimistic Locking)

```php
// app/Domain/Booking/Services/BookingCreationService.php

public function create(CreateBookingDTO $dto): Appointment
{
    return DB::transaction(function () use ($dto) {

        // Lock the staff row to prevent concurrent bookings to same slot
        Staff::lockForUpdate()->findOrFail($dto->staffId);

        $result = $this->slotEngine->validateSlot(
            service:  Service::findOrFail($dto->serviceId),
            staff:    Staff::findOrFail($dto->staffId),
            startsAt: $dto->startsAt,
        );

        if (!$result->isOk()) {
            throw new SlotNotAvailableException($result->reason());
        }

        $appointment = Appointment::create([
            'tenant_id'    => tenant('id'),
            'service_id'   => $dto->serviceId,
            'staff_id'     => $dto->staffId,
            'customer_id'  => $dto->customerId,
            'starts_at'    => $dto->startsAt->utc(),
            'ends_at'      => $dto->startsAt->copy()->addMinutes($dto->service->duration_minutes)->utc(),
            'ends_at_with_buffer' => $dto->startsAt->copy()->addMinutes($dto->service->total_duration)->utc(),
            'price'        => $dto->price,
            'status'       => AppointmentStatus::PENDING,
            'timezone'     => $dto->timezone,
            'notes'        => $dto->notes,
            'source'       => $dto->source, // 'web', 'app', 'walk_in', 'phone'
        ]);

        // Invalidate slot cache
        Cache::forget("slots:{$dto->staffId}:{$dto->serviceId}:{$dto->startsAt->toDateString()}");

        event(new AppointmentCreated($appointment));

        return $appointment;
    }, attempts: 3); // Retry on deadlock
}
```

---

### 1.4 Appointments Table (Core)

```sql
CREATE TABLE appointments (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid             CHAR(26) NOT NULL UNIQUE,      -- public-facing ID (not sequential)
    service_id       BIGINT UNSIGNED NOT NULL,
    staff_id         BIGINT UNSIGNED NOT NULL,
    customer_id      BIGINT UNSIGNED NOT NULL,
    resource_id      BIGINT UNSIGNED,
    group_id         BIGINT UNSIGNED,               -- for group bookings
    recurring_id     BIGINT UNSIGNED,               -- link to RecurringRule
    starts_at        DATETIME NOT NULL,             -- UTC
    ends_at          DATETIME NOT NULL,             -- UTC (service end, no buffer)
    ends_at_with_buffer DATETIME NOT NULL,          -- UTC (includes after-buffer)
    timezone         VARCHAR(50) NOT NULL,
    status           ENUM(
                       'pending','confirmed','completed',
                       'cancelled','no_show','rescheduled'
                     ) NOT NULL DEFAULT 'pending',
    price            DECIMAL(10,2) NOT NULL,
    deposit_paid     DECIMAL(10,2) DEFAULT 0,
    discount_amount  DECIMAL(10,2) DEFAULT 0,
    notes            TEXT,
    internal_notes   TEXT,
    source           ENUM('online','app','walk_in','phone','api') DEFAULT 'online',
    cancelled_by     ENUM('customer','staff','system'),
    cancel_reason    VARCHAR(255),
    cancelled_at     DATETIME,
    confirmed_at     DATETIME,
    completed_at     DATETIME,
    no_show_at       DATETIME,
    reminder_sent_at DATETIME,
    metadata         JSON,
    created_at       TIMESTAMP,
    updated_at       TIMESTAMP,
    deleted_at       TIMESTAMP,

    FOREIGN KEY (service_id)  REFERENCES services(id),
    FOREIGN KEY (staff_id)    REFERENCES staff(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),

    -- Critical performance indexes
    INDEX idx_staff_time   (staff_id, starts_at, ends_at_with_buffer),
    INDEX idx_customer     (customer_id, starts_at),
    INDEX idx_status_date  (status, starts_at),
    INDEX idx_ulid         (ulid)
);

-- Recurring Rules
CREATE TABLE recurring_rules (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    frequency       ENUM('daily','weekly','biweekly','monthly') NOT NULL,
    interval        TINYINT NOT NULL DEFAULT 1,
    days_of_week    JSON,       -- [1,3,5] for Mon,Wed,Fri
    ends_on         DATE,
    max_occurrences SMALLINT,
    created_at      TIMESTAMP
);

-- Appointment Status History (full audit trail)
CREATE TABLE appointment_status_history (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_id BIGINT UNSIGNED NOT NULL,
    from_status    VARCHAR(20),
    to_status      VARCHAR(20) NOT NULL,
    changed_by     BIGINT UNSIGNED,
    reason         VARCHAR(255),
    created_at     TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    INDEX idx_appointment (appointment_id, created_at)
);
```

---

### 1.5 Customers Table

```sql
CREATE TABLE customers (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name    VARCHAR(100) NOT NULL,
    last_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(180),
    phone         VARCHAR(30),
    phone_country VARCHAR(3),
    dob           DATE,
    gender        ENUM('male','female','other','prefer_not'),
    avatar        VARCHAR(255),
    language      VARCHAR(5) DEFAULT 'en',
    timezone      VARCHAR(50) DEFAULT 'UTC',
    notes         TEXT,
    tags          JSON,
    is_blocked    BOOLEAN DEFAULT FALSE,
    gdpr_consent  BOOLEAN DEFAULT FALSE,
    gdpr_date     TIMESTAMP,
    total_spent   DECIMAL(12,2) DEFAULT 0,   -- denormalized for performance
    total_visits  INT DEFAULT 0,              -- denormalized
    last_visit_at DATETIME,
    ltv_tier      ENUM('new','regular','loyal','vip') DEFAULT 'new',
    created_at    TIMESTAMP,
    updated_at    TIMESTAMP,
    deleted_at    TIMESTAMP,

    INDEX idx_email    (email),
    INDEX idx_phone    (phone),
    INDEX idx_ltv_tier (ltv_tier),
    FULLTEXT idx_name_search (first_name, last_name)
);
```

---

### 1.6 Public Booking Page

#### Architecture Overview

Each tenant gets a public booking page at `{subdomain}.velora.test/book` (or custom domain). This page must:
- Be mobile-first (PWA-ready)
- Support all 15 languages
- Auto-detect visitor language
- Show only active, online-bookable services
- Allow guest checkout (no registration required)
- Show real-time slot availability via API polling

#### Routes (Public, no auth)

```php
// routes/tenant.php
Route::prefix('book')->name('booking.')->group(function () {
    Route::get('/',                          [PublicBookingController::class, 'index'])->name('index');
    Route::get('/services',                  [PublicBookingController::class, 'services'])->name('services');
    Route::get('/services/{service}/staff',  [PublicBookingController::class, 'staff'])->name('staff');
    Route::get('/slots',                     [PublicBookingController::class, 'slots'])->name('slots');
    Route::post('/reserve',                  [PublicBookingController::class, 'reserve'])->name('reserve');
    Route::get('/confirm/{ulid}',            [PublicBookingController::class, 'confirm'])->name('confirm');
    Route::get('/cancel/{ulid}/{token}',     [PublicBookingController::class, 'cancel'])->name('cancel');
});
```

#### Booking Flow Steps

```
Step 1: Service Selection
  → GET /book/services
  → Returns active, online-bookable services grouped by category
  → Translated to visitor's language

Step 2: Staff Selection  
  → GET /book/services/{service}/staff
  → Returns staff who can perform this service
  → "Any available" option always present

Step 3: Date + Slot Selection
  → GET /book/slots?service={id}&staff={id}&date={Y-m-d}
  → Returns available slots for that day
  → If "Any" selected: aggregates all staff availability

Step 4: Customer Details
  → Name, Email, Phone, Notes
  → GDPR consent checkbox
  → No registration required

Step 5: Payment (if deposit required)
  → Stripe Elements embedded
  → Or "Pay at venue" option

Step 6: Confirmation
  → Booking confirmation page
  → Email/SMS confirmation sent
  → Add to calendar link (ICS)
```

---

## Phase 2 — Payments & Billing

### 2.1 Payment Driver Architecture

```php
// app/Infrastructure/Payment/Contracts/PaymentGatewayInterface.php

interface PaymentGatewayInterface
{
    public function createPaymentIntent(PaymentIntentDTO $dto): PaymentIntentResult;
    public function capturePayment(string $paymentIntentId): CaptureResult;
    public function refund(string $chargeId, float $amount): RefundResult;
    public function createCustomer(CustomerDTO $dto): string; // returns gateway customer ID
    public function handleWebhook(Request $request): WebhookResult;
    public function supports(): array; // ['card', 'apple_pay', 'google_pay']
}
```

```php
// app/Infrastructure/Payment/Drivers/StripeDriver.php

class StripeDriver implements PaymentGatewayInterface
{
    public function __construct(
        private StripeClient $stripe,
        private string $webhookSecret
    ) {}

    public function createPaymentIntent(PaymentIntentDTO $dto): PaymentIntentResult
    {
        $intent = $this->stripe->paymentIntents->create([
            'amount'               => (int)($dto->amount * 100),
            'currency'             => strtolower($dto->currency),
            'customer'             => $dto->gatewayCustomerId,
            'payment_method_types' => ['card'],
            'metadata'             => [
                'appointment_ulid' => $dto->appointmentUlid,
                'tenant_id'        => $dto->tenantId,
            ],
            'capture_method'       => $dto->captureImmediately ? 'automatic' : 'manual',
        ]);

        return new PaymentIntentResult(
            clientSecret: $intent->client_secret,
            paymentIntentId: $intent->id,
        );
    }

    public function refund(string $chargeId, float $amount): RefundResult
    {
        $refund = $this->stripe->refunds->create([
            'payment_intent' => $chargeId,
            'amount'         => (int)($amount * 100),
        ]);

        return new RefundResult(
            refundId: $refund->id,
            status: $refund->status,
        );
    }
}
```

### 2.2 Payments Database Schema

```sql
-- Payment Transactions
CREATE TABLE payment_transactions (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ulid                CHAR(26) NOT NULL UNIQUE,
    appointment_id      BIGINT UNSIGNED,
    invoice_id          BIGINT UNSIGNED,
    customer_id         BIGINT UNSIGNED NOT NULL,
    gateway             VARCHAR(30) NOT NULL,   -- 'stripe', 'paymob', 'cash'
    gateway_id          VARCHAR(255),           -- stripe PaymentIntent ID
    type                ENUM('payment','deposit','refund','partial_refund') NOT NULL,
    status              ENUM('pending','succeeded','failed','refunded') NOT NULL,
    amount              DECIMAL(10,2) NOT NULL,
    currency            CHAR(3) NOT NULL DEFAULT 'USD',
    fee                 DECIMAL(8,2) DEFAULT 0, -- gateway fee
    net_amount          DECIMAL(10,2),          -- amount - fee
    metadata            JSON,
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP,
    INDEX idx_appointment (appointment_id),
    INDEX idx_customer    (customer_id, created_at),
    INDEX idx_gateway_id  (gateway, gateway_id)
);

-- Invoices
CREATE TABLE invoices (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number   VARCHAR(30) NOT NULL UNIQUE,
    appointment_id   BIGINT UNSIGNED,
    customer_id      BIGINT UNSIGNED NOT NULL,
    subtotal         DECIMAL(10,2) NOT NULL,
    discount         DECIMAL(10,2) DEFAULT 0,
    tax_rate         DECIMAL(5,2) DEFAULT 0,
    tax_amount       DECIMAL(10,2) DEFAULT 0,
    total            DECIMAL(10,2) NOT NULL,
    paid_amount      DECIMAL(10,2) DEFAULT 0,
    due_date         DATE,
    status           ENUM('draft','sent','paid','partially_paid','overdue','void') DEFAULT 'draft',
    notes            TEXT,
    pdf_path         VARCHAR(255),
    sent_at          TIMESTAMP,
    paid_at          TIMESTAMP,
    created_at       TIMESTAMP,
    updated_at       TIMESTAMP,
    INDEX idx_customer_status (customer_id, status),
    INDEX idx_number (invoice_number)
);

-- Invoice Line Items
CREATE TABLE invoice_items (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id  BIGINT UNSIGNED NOT NULL,
    description JSON NOT NULL,
    quantity    DECIMAL(8,2) NOT NULL DEFAULT 1,
    unit_price  DECIMAL(10,2) NOT NULL,
    total       DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- Staff Commissions
CREATE TABLE staff_commissions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id        BIGINT UNSIGNED NOT NULL,
    appointment_id  BIGINT UNSIGNED NOT NULL,
    transaction_id  BIGINT UNSIGNED,
    gross_amount    DECIMAL(10,2) NOT NULL,
    commission_type ENUM('fixed','percent') NOT NULL,
    commission_rate DECIMAL(8,2) NOT NULL,
    commission_amt  DECIMAL(10,2) NOT NULL,
    is_paid         BOOLEAN DEFAULT FALSE,
    paid_at         TIMESTAMP,
    created_at      TIMESTAMP,
    FOREIGN KEY (staff_id)       REFERENCES staff(id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    INDEX idx_staff_paid (staff_id, is_paid)
);
```

---

## Phase 3 — Automation & Smart Logic

### 3.1 Reminder System

#### Architecture

```php
// app/Infrastructure/Sms/Contracts/SmsProviderInterface.php
interface SmsProviderInterface
{
    public function send(string $to, string $message, array $options = []): SmsResult;
    public function getName(): string;
}

// Drivers: Twilio, Vonage, WhatsApp Business API, local providers
```

#### Reminder Rules Schema

```sql
CREATE TABLE reminder_rules (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            JSON NOT NULL,
    trigger_type    ENUM('before_appointment','after_appointment','after_booking','on_cancellation') NOT NULL,
    trigger_minutes INT NOT NULL,        -- e.g., 1440 = 24 hours before
    channel         ENUM('email','sms','push','whatsapp') NOT NULL,
    template_key    VARCHAR(100) NOT NULL,
    is_active       BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMP
);

CREATE TABLE reminder_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_id  BIGINT UNSIGNED NOT NULL,
    rule_id         BIGINT UNSIGNED NOT NULL,
    channel         VARCHAR(20) NOT NULL,
    status          ENUM('sent','failed','skipped') NOT NULL,
    sent_at         TIMESTAMP,
    error           TEXT,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    INDEX idx_appointment_rule (appointment_id, rule_id)
);
```

#### Queue Job

```php
// app/Jobs/SendAppointmentReminder.php
class SendAppointmentReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly int $appointmentId,
        public readonly int $ruleId,
    ) {}

    public function handle(NotificationDispatcher $dispatcher): void
    {
        $appointment = Appointment::with(['customer', 'service', 'staff'])
            ->findOrFail($this->appointmentId);

        // Skip if appointment cancelled/completed
        if (!$appointment->isUpcoming()) {
            $this->delete();
            return;
        }

        // Skip if already sent
        if (ReminderLog::alreadySent($this->appointmentId, $this->ruleId)) {
            $this->delete();
            return;
        }

        $rule = ReminderRule::findOrFail($this->ruleId);
        $dispatcher->send($appointment, $rule);

        ReminderLog::record($this->appointmentId, $this->ruleId, 'sent');
    }
}
```

---

### 3.2 Waiting List System

```sql
CREATE TABLE waiting_list (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id     BIGINT UNSIGNED NOT NULL,
    staff_id       BIGINT UNSIGNED,            -- null = any staff
    customer_id    BIGINT UNSIGNED NOT NULL,
    preferred_date DATE NOT NULL,
    preferred_time TIME,
    status         ENUM('waiting','notified','booked','expired','cancelled') DEFAULT 'waiting',
    notified_at    TIMESTAMP,
    expires_at     TIMESTAMP,
    created_at     TIMESTAMP,
    updated_at     TIMESTAMP,
    INDEX idx_service_date (service_id, preferred_date, status),
    INDEX idx_customer (customer_id)
);
```

```php
// When a booking is cancelled, auto-notify waiting list:
class NotifyWaitingList implements ShouldQueue
{
    public function handle(AppointmentCancelled $event): void
    {
        $appointment = $event->appointment;

        $waiters = WaitingList::query()
            ->where('service_id', $appointment->service_id)
            ->where('preferred_date', $appointment->starts_at->toDateString())
            ->where('status', 'waiting')
            ->orderBy('created_at') // FIFO
            ->limit(3) // notify top 3
            ->get();

        foreach ($waiters as $waiter) {
            $waiter->update([
                'status'      => 'notified',
                'notified_at' => now(),
                'expires_at'  => now()->addHours(2), // 2 hour window to book
            ]);

            dispatch(new SendWaitingListNotification($waiter));
        }
    }
}
```

---

### 3.3 Business Rules Engine

```sql
CREATE TABLE business_rules (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         JSON NOT NULL,
    type         ENUM(
                   'cancellation_policy',
                   'deposit_policy',
                   'no_show_penalty',
                   'peak_pricing',
                   'loyalty_discount'
                 ) NOT NULL,
    conditions   JSON NOT NULL,   -- rule conditions (hours_before, day_of_week, etc.)
    actions      JSON NOT NULL,   -- what to do (charge_fee, apply_discount, etc.)
    is_active    BOOLEAN DEFAULT TRUE,
    priority     TINYINT DEFAULT 0,
    created_at   TIMESTAMP
);
```

**Example Rule (JSON):**
```json
{
  "type": "cancellation_policy",
  "conditions": { "hours_before": 24 },
  "actions": {
    "charge": { "type": "percent", "value": 50 },
    "message": "cancellation_late_fee_msg"
  }
}
```

---

## Phase 4 — Analytics & Reporting

### 4.1 Analytics Schema

```sql
-- Daily snapshots (pre-aggregated for performance)
CREATE TABLE analytics_daily (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date                DATE NOT NULL,
    total_bookings      INT DEFAULT 0,
    confirmed_bookings  INT DEFAULT 0,
    completed_bookings  INT DEFAULT 0,
    cancelled_bookings  INT DEFAULT 0,
    no_shows            INT DEFAULT 0,
    new_customers       INT DEFAULT 0,
    returning_customers INT DEFAULT 0,
    gross_revenue       DECIMAL(12,2) DEFAULT 0,
    net_revenue         DECIMAL(12,2) DEFAULT 0,
    refunds             DECIMAL(12,2) DEFAULT 0,
    avg_booking_value   DECIMAL(8,2) DEFAULT 0,
    utilization_pct     DECIMAL(5,2) DEFAULT 0,
    UNIQUE KEY uq_date (date),
    INDEX idx_date (date)
);

-- Staff performance daily
CREATE TABLE staff_analytics_daily (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id        BIGINT UNSIGNED NOT NULL,
    date            DATE NOT NULL,
    bookings_count  INT DEFAULT 0,
    completed       INT DEFAULT 0,
    cancelled       INT DEFAULT 0,
    no_shows        INT DEFAULT 0,
    revenue         DECIMAL(12,2) DEFAULT 0,
    utilization_pct DECIMAL(5,2) DEFAULT 0,
    avg_rating      DECIMAL(3,2),
    UNIQUE KEY uq_staff_date (staff_id, date),
    INDEX idx_staff_date (staff_id, date)
);

-- Booking heatmap (hour-of-day × day-of-week)
CREATE TABLE booking_heatmap (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    day_of_week   TINYINT NOT NULL,  -- 0-6
    hour_of_day   TINYINT NOT NULL,  -- 0-23
    bookings_count INT DEFAULT 0,
    week_start    DATE NOT NULL,
    UNIQUE KEY uq_week_slot (week_start, day_of_week, hour_of_day)
);
```

### 4.2 Analytics Service (Super Admin — Cross Tenant)

```php
// app/Domain/Analytics/Services/PlatformAnalyticsService.php
// Runs on central DB, reads from anonymized aggregate tables

class PlatformAnalyticsService
{
    public function getRevenueForecasting(int $months = 3): array
    {
        // Uses linear regression on past 12 months of MRR data
        // Returns forecasted MRR for next N months
    }

    public function getChurnRate(Carbon $period): float
    {
        // Churned = subscriptions that ended in period / start-of-period active
        $startActive = $this->getActiveCount($period->copy()->startOfMonth());
        $churned     = $this->getChurnedInPeriod($period);
        return $startActive > 0 ? round(($churned / $startActive) * 100, 2) : 0;
    }

    public function getLtvByTier(): array
    {
        // Average revenue per customer grouped by ltv_tier
    }
}
```

---

## Phase 5 — Enterprise & Scale

### 5.1 API Architecture

```php
// routes/api.php — V1

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Public endpoints (no auth)
    Route::prefix('public/{tenant}')->group(function () {
        Route::get('services',           [PublicApiController::class, 'services']);
        Route::get('staff',              [PublicApiController::class, 'staff']);
        Route::get('slots',              [PublicApiController::class, 'slots']);
        Route::post('appointments',      [PublicApiController::class, 'createAppointment']);
    });

    // Tenant API (Bearer token)
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::apiResource('appointments',    AppointmentApiController::class);
        Route::apiResource('services',        ServiceApiController::class);
        Route::apiResource('staff',           StaffApiController::class);
        Route::apiResource('customers',       CustomerApiController::class);
        Route::apiResource('invoices',        InvoiceApiController::class);
        Route::apiResource('waiting-list',    WaitingListApiController::class);

        Route::post('appointments/{ulid}/confirm',   [AppointmentApiController::class, 'confirm']);
        Route::post('appointments/{ulid}/cancel',    [AppointmentApiController::class, 'cancel']);
        Route::post('appointments/{ulid}/complete',  [AppointmentApiController::class, 'complete']);
        Route::post('appointments/{ulid}/no-show',   [AppointmentApiController::class, 'noShow']);
        Route::post('appointments/{ulid}/reschedule',[AppointmentApiController::class, 'reschedule']);
    });

    // Webhooks
    Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle']);
});
```

### 5.2 Redis Caching Strategy

```php
// Cache TTL Strategy:
// - Slot availability: 5 min (TTL 300s) — invalidated on booking create/update
// - Service list: 60 min — invalidated on service update
// - Staff list: 60 min — invalidated on staff update
// - Daily analytics: 15 min
// - Business hours: 24h

// Cache Tags (for grouped invalidation):
Cache::tags(["tenant:{$tenantId}:services"])->remember($key, 3600, fn() => ...);

// When service updated:
Cache::tags(["tenant:{$tenantId}:services"])->flush();
```

### 5.3 Queue Configuration (Scalable)

```php
// config/queue.php — Multiple Queues with Priority

'connections' => [
    'redis' => [
        'driver'  => 'redis',
        'queue'   => 'default',
        // Separate queues by priority:
        // high:    payments, confirmations
        // default: reminders, stats update
        // low:     exports, report generation, analytics aggregation
    ]
],
```

```bash
# Supervisor config for workers
[program:velora-queue-high]
command=php artisan queue:work redis --queue=high --tries=3 --timeout=30

[program:velora-queue-default]
command=php artisan queue:work redis --queue=default,low --tries=3 --timeout=60
numprocs=4
```

### 5.4 Security & GDPR

```sql
-- GDPR Consent Logs
CREATE TABLE gdpr_consents (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id  BIGINT UNSIGNED NOT NULL,
    type         ENUM('marketing','data_processing','cookies') NOT NULL,
    granted      BOOLEAN NOT NULL,
    ip_address   VARCHAR(45),
    user_agent   VARCHAR(255),
    created_at   TIMESTAMP,
    INDEX idx_customer (customer_id, type)
);

-- Data Export Requests
CREATE TABLE data_export_requests (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id  BIGINT UNSIGNED NOT NULL,
    requested_at TIMESTAMP NOT NULL,
    status       ENUM('pending','processing','ready','delivered','expired') DEFAULT 'pending',
    file_path    VARCHAR(255),
    expires_at   TIMESTAMP,
    created_at   TIMESTAMP
);

-- Right-to-Delete Requests
CREATE TABLE deletion_requests (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id  BIGINT UNSIGNED NOT NULL,
    requested_at TIMESTAMP NOT NULL,
    status       ENUM('pending','approved','completed','rejected') DEFAULT 'pending',
    processed_at TIMESTAMP,
    processed_by BIGINT UNSIGNED,
    notes        TEXT,
    created_at   TIMESTAMP
);
```

---

## Phase 6 — Marketplace

### 6.1 Architecture Overview

The marketplace runs on the **central database** and is a public discovery layer. Tenants opt-in to be listed. Revenue model: featured listings fee + commission on marketplace-driven bookings.

```sql
-- Central DB
CREATE TABLE marketplace_listings (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(200) NOT NULL,
    slug            VARCHAR(200) NOT NULL UNIQUE,
    description     JSON,
    category        VARCHAR(50) NOT NULL,    -- 'salon','clinic','fitness','wellness'
    subcategories   JSON,
    country         CHAR(2) NOT NULL,
    city            VARCHAR(100),
    latitude        DECIMAL(10,7),
    longitude       DECIMAL(10,7),
    address         TEXT,
    phone           VARCHAR(30),
    email           VARCHAR(180),
    website         VARCHAR(255),
    cover_image     VARCHAR(255),
    images          JSON,
    avg_rating      DECIMAL(3,2) DEFAULT 0,
    review_count    INT DEFAULT 0,
    is_featured     BOOLEAN DEFAULT FALSE,
    is_verified     BOOLEAN DEFAULT FALSE,
    is_active       BOOLEAN DEFAULT TRUE,
    rank_score      DECIMAL(8,4) DEFAULT 0,  -- computed ranking
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    FULLTEXT idx_search (name, description),
    INDEX idx_geo    (country, city),
    INDEX idx_cat    (category, is_active, rank_score),
    SPATIAL INDEX idx_location (point_column)  -- for geo queries
);

CREATE TABLE marketplace_reviews (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    listing_id  BIGINT UNSIGNED NOT NULL,
    reviewer_name VARCHAR(100),
    rating      TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review      TEXT,
    is_verified BOOLEAN DEFAULT FALSE,   -- verified booking
    reply       TEXT,
    replied_at  TIMESTAMP,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at  TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES marketplace_listings(id),
    INDEX idx_listing_approved (listing_id, is_approved, rating)
);
```

### 6.2 Ranking Algorithm

```php
class ListingRankingService
{
    public function computeScore(MarketplaceListing $listing): float
    {
        $score = 0;

        // Base quality score (0-40)
        $score += ($listing->avg_rating / 5) * 20;
        $score += min($listing->review_count / 100, 1) * 10;
        $score += $listing->is_verified ? 10 : 0;

        // Activity score (0-30) — recent bookings via platform
        $recentBookings = $this->getMarketplaceBookingsLast30Days($listing->tenant_id);
        $score += min($recentBookings / 50, 1) * 30;

        // Profile completeness (0-20)
        $score += $this->profileCompletenessPct($listing) * 0.20;

        // Featured boost (0-10)
        $score += $listing->is_featured ? 10 : 0;

        return round($score, 4);
    }
}
```

---

## Phase 7 — Mobile Layer

### 7.1 API Design for Mobile Apps

#### Customer App Endpoints

```
GET    /api/v1/mobile/customer/home
POST   /api/v1/mobile/customer/auth/register
POST   /api/v1/mobile/customer/auth/login
POST   /api/v1/mobile/customer/auth/social     (Google/Apple OAuth)

GET    /api/v1/mobile/customer/appointments
POST   /api/v1/mobile/customer/appointments
GET    /api/v1/mobile/customer/appointments/{ulid}
POST   /api/v1/mobile/customer/appointments/{ulid}/cancel

GET    /api/v1/mobile/customer/history
GET    /api/v1/mobile/customer/loyalty
POST   /api/v1/mobile/customer/push-token      (FCM/APNs token registration)
```

#### Staff App Endpoints

```
GET    /api/v1/mobile/staff/schedule            (today's calendar)
GET    /api/v1/mobile/staff/schedule/{date}
PATCH  /api/v1/mobile/staff/appointments/{ulid}/status
GET    /api/v1/mobile/staff/customers/{id}
POST   /api/v1/mobile/staff/walk-in             (create walk-in booking)
GET    /api/v1/mobile/staff/earnings            (commission summary)
```

### 7.2 Push Notification Schema

```sql
-- Tenant DB
CREATE TABLE push_tokens (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_type  ENUM('customer','staff') NOT NULL,
    owner_id    BIGINT UNSIGNED NOT NULL,
    platform    ENUM('android','ios','web') NOT NULL,
    token       VARCHAR(512) NOT NULL,
    is_active   BOOLEAN DEFAULT TRUE,
    last_used   TIMESTAMP,
    created_at  TIMESTAMP,
    UNIQUE KEY uq_token (token),
    INDEX idx_owner (owner_type, owner_id, is_active)
);
```

---

## Full ERD & Database Schema

### Central Database Tables

```
tenants
├── id, ulid, name, slug, db_name, db_host
├── is_active, plan_id, trial_ends_at
└── created_at, updated_at

domains
├── id, tenant_id, domain, is_primary, is_custom
└── ssl_verified_at

subscription_plans
├── id, name(JSON), price, billing_cycle
├── features(JSON), limits(JSON)
└── is_active, sort_order

tenant_subscriptions
├── id, tenant_id, plan_id
├── status, starts_at, ends_at
├── stripe_subscription_id
└── created_at

marketplace_listings ──────┐
marketplace_reviews        │
                           ▼
                        tenant_id (links to tenant)
```

### Tenant Database Entity Relationships

```
service_categories ──< services >──< staff_services >── staff
                                  └──< service_resources >── resources

staff ──< staff_working_hours
      └──< staff_breaks
      └──< staff_time_off
      └──< staff_analytics_daily
      └──< staff_commissions

customers ──< appointments ──── service
          └──< payment_transactions
          └──< invoices
          └──< waiting_list
          └──< push_tokens
          └──< gdpr_consents

appointments ──── appointment_status_history
             ──── recurring_rules
             ──── reminder_logs ──── reminder_rules
             ──── payment_transactions
             ──── invoices

analytics_daily (daily aggregated snapshot)
booking_heatmap (hour × day aggregation)
```

### Approximate Table Count

| Domain | Tables |
|--------|--------|
| Services & Resources | 4 |
| Staff & Availability | 5 |
| Bookings / Appointments | 4 |
| Customers | 4 |
| Payments & Invoices | 4 |
| Notifications & Reminders | 3 |
| Waiting List & Rules | 3 |
| Analytics | 4 |
| GDPR & Audit | 4 |
| Push / Mobile | 1 |
| **Total (Tenant DB)** | **~36** |
| Central DB | **~10** |

---

## Testing Strategy

### Unit Tests

```php
// tests/Unit/Domain/Booking/SlotEngineTest.php

class SlotEngineTest extends TestCase
{
    /** @test */
    public function it_returns_no_slots_when_staff_has_no_working_hours(): void
    public function it_excludes_slots_that_overlap_existing_bookings(): void
    public function it_respects_buffer_time_between_bookings(): void
    public function it_handles_timezone_conversion_correctly(): void
    public function it_returns_empty_on_holiday(): void
    public function it_respects_staff_break_times(): void
    public function it_handles_recurring_booking_conflicts(): void
    public function it_generates_correct_slot_intervals(): void
}

class BusinessRulesEngineTest extends TestCase
{
    /** @test */
    public function it_applies_cancellation_fee_when_within_policy_window(): void
    public function it_skips_fee_when_outside_cancellation_window(): void
    public function it_calculates_peak_hour_pricing_correctly(): void
    public function it_applies_loyalty_discount_for_vip_tier(): void
}
```

### Feature Tests

```php
// tests/Feature/Api/V1/BookingFlowTest.php

class BookingFlowTest extends TestCase
{
    use RefreshDatabase, WithFaker, TenantSetup;

    /** @test */
    public function guest_can_complete_full_booking_flow(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $staff   = Staff::factory()->withWorkingHours()->create();
        $staff->services()->attach($service);

        // Get slots
        $response = $this->getJson("/api/v1/public/{$this->tenant}/slots?service={$service->id}&staff={$staff->id}&date=".now()->addDay()->toDateString());
        $response->assertOk()->assertJsonStructure(['slots']);

        $slot = $response->json('slots.0');

        // Create booking
        $response = $this->postJson("/api/v1/public/{$this->tenant}/appointments", [
            'service_id'   => $service->id,
            'staff_id'     => $staff->id,
            'starts_at'    => $slot,
            'customer'     => ['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '+1234567890'],
            'gdpr_consent' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('appointments', ['status' => 'pending']);
    }

    /** @test */
    public function concurrent_booking_attempts_do_not_double_book(): void
    {
        // Simulate race condition with two simultaneous requests
        $responses = collect(range(1, 2))->map(fn() =>
            $this->postJson('/api/v1/...', $this->validBookingPayload())
        );

        // Exactly one must succeed, one must fail with 409
        $this->assertEquals(1, $responses->where('status', 201)->count());
        $this->assertEquals(1, $responses->where('status', 409)->count());
    }
}
```

### Load Testing Strategy

```yaml
# k6 load test plan — booking_slot_check.js
scenarios:
  spike_slot_availability:
    executor: ramping-vus
    stages:
      - { duration: 1m,  target: 100  }   # Ramp to 100 users
      - { duration: 5m,  target: 1000 }   # Spike to 1000 users
      - { duration: 2m,  target: 0    }   # Ramp down
    thresholds:
      http_req_duration: [p(95) < 200ms]  # 95th percentile < 200ms
      http_req_failed:   [rate < 0.01]    # < 1% error rate
```

---

## Implementation Roadmap

### Sprint 1 (Weeks 1–2): Foundation
- [ ] Migrations: services, categories, resources, staff tables
- [ ] Staff working hours + breaks + time-off
- [ ] Holiday table
- [ ] Seeders for dev environment

### Sprint 2 (Weeks 3–4): Booking Engine Core
- [ ] `SlotEngine` service + unit tests
- [ ] `BookingCreationService` + race condition tests
- [ ] `Appointment` model + status machine
- [ ] `AppointmentStatusHistory` observer
- [ ] Events: AppointmentCreated, AppointmentCancelled, etc.

### Sprint 3 (Weeks 5–6): Public Booking Page
- [ ] Blade + API routes for public booking flow
- [ ] Mobile-first UI (Tailwind)
- [ ] Multi-language booking page
- [ ] Guest checkout form
- [ ] Email confirmation

### Sprint 4 (Weeks 7–8): Payments
- [ ] `PaymentGatewayInterface` + Stripe driver
- [ ] Deposit flow
- [ ] Refund flow
- [ ] Stripe webhooks
- [ ] Invoice generation (PDF via Spatie/Laravel PDF)

### Sprint 5 (Weeks 9–10): Automation
- [ ] Reminder rules engine
- [ ] Queue-based reminder jobs
- [ ] Waiting list + auto-notify
- [ ] No-show detection (Scheduled command)

### Sprint 6 (Weeks 11–12): Analytics
- [ ] Daily analytics aggregation command (run 00:10 UTC daily)
- [ ] Super admin analytics dashboard
- [ ] Tenant analytics dashboard
- [ ] Booking heatmap

### Sprint 7 (Weeks 13–14): API + Mobile
- [ ] Versioned RESTful API (v1)
- [ ] Sanctum token scopes
- [ ] Push notification service
- [ ] Mobile app API docs (Scribe/Swagger)

### Sprint 8 (Weeks 15–16): Marketplace
- [ ] Marketplace listing management (tenant opt-in)
- [ ] Public discovery page
- [ ] Review system
- [ ] Ranking algorithm cron

---

## Scaling Considerations

### At 100,000 Tenants

| Concern | Solution |
|---------|---------|
| Tenant DB per row overhead | Use MySQL table-per-tenant OR shared DB with tenant_id — evaluate at 10k tenants |
| Slot calculation at scale | Pre-generate and cache daily slot availability; invalidate on booking |
| Analytics aggregation | Nightly cron per tenant — use Queue to parallelize |
| File storage | S3-compatible (MinIO for on-prem, AWS S3 for cloud) per tenant prefix |
| Search | Meilisearch or Typesense for customer/booking search (not SQL LIKE) |

### At Millions of Bookings/Month

| Concern | Solution |
|---------|---------|
| `appointments` table growth | Partition by year-month on `starts_at` |
| Analytics queries slow | Pre-aggregate to daily snapshots; use ClickHouse for BI |
| PDF/CSV exports blocking | Always async via Queue; store in S3; email download link |
| Slot engine cache invalidation storm | Use staggered TTLs + cache stampede prevention (`swr` pattern) |
| Queue worker scaling | Add workers horizontally; separate queues by priority and tenant tier |

### Database Indexing Rules (Non-Negotiable)

```sql
-- Every appointments query must hit one of these indexes:
-- 1. (staff_id, starts_at, ends_at_with_buffer)  ← slot conflict check
-- 2. (customer_id, starts_at)                     ← customer history
-- 3. (status, starts_at)                          ← dashboard filters
-- 4. (ulid)                                       ← public-facing lookups

-- NEVER query appointments without a WHERE clause on one of these columns.
-- Enforce via Eloquent scope or architectural lint rule.
```

---

*Velora Masterplan — Built for global scale from day one.*
*Version 1.0 — March 2, 2026*
