<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendAppointmentReminderEmail;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\NotificationDelivery;
use App\Models\ReminderLog;
use App\Models\ReminderRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class AppointmentReminderDeliveryTest extends TenantTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function twenty_four_hour_rule_creates_one_idempotent_queued_delivery(): void
    {
        Queue::fake();

        $start = Carbon::create(2026, 9, 1, 9, 0, 0, config('app.timezone'));
        Carbon::setTestNow($start->copy()->subDay());

        $appointment = $this->makeAppointment($start);
        $rule = $this->makeRule(1440);

        $this->runReminderCommand();

        $delivery = NotificationDelivery::query()
            ->where('appointment_id', $appointment->id)
            ->where('event', 'appointment.reminder_24h')
            ->firstOrFail();

        self::assertSame(
            'appointment.reminder_24h|email|' . $appointment->public_reference,
            $delivery->dedupe_key,
        );
        self::assertSame('queued', $delivery->status);
        self::assertSame(0, $delivery->attempts);
        self::assertSame('email', $delivery->channel);

        Queue::assertPushed(SendAppointmentReminderEmail::class, function (SendAppointmentReminderEmail $job) use ($delivery, $appointment): bool {
            return $job->deliveryId === $delivery->id
                && $job->data['appointment_id'] === $appointment->id
                && $job->data['customer_type'] === 'customer';
        });

        self::assertDatabaseHas('reminder_logs', [
            'id' => $delivery->metadata['reminder_log_id'],
            'appointment_id' => $appointment->id,
            'rule_id' => $rule->id,
            'status' => 'pending',
        ]);

        $this->runReminderCommand();

        self::assertSame(
            1,
            NotificationDelivery::query()
                ->where('dedupe_key', $delivery->dedupe_key)
                ->count(),
        );
    }

    #[Test]
    public function one_hour_rule_uses_distinct_event_and_dedupe_key(): void
    {
        Queue::fake();

        $start = Carbon::create(2026, 9, 1, 10, 30, 0, config('app.timezone'));
        Carbon::setTestNow($start->copy()->subHour());

        $appointment = $this->makeAppointment($start);
        $this->makeRule(60);

        $this->runReminderCommand();

        $delivery = NotificationDelivery::query()
            ->where('appointment_id', $appointment->id)
            ->firstOrFail();

        self::assertSame('appointment.reminder_1h', $delivery->event);
        self::assertSame(
            'appointment.reminder_1h|email|' . $appointment->public_reference,
            $delivery->dedupe_key,
        );

        Queue::assertPushed(SendAppointmentReminderEmail::class);
    }

    #[Test]
    public function reminder_job_marks_delivery_and_legacy_log_sent_for_new_customer(): void
    {
        Mail::fake();

        $start = Carbon::create(2026, 9, 1, 11, 0, 0, config('app.timezone'));
        Carbon::setTestNow($start->copy()->subHour());

        $appointment = $this->makeAppointment($start);
        $rule = $this->makeRule(60);
        $customer = $appointment->newCustomer;

        $log = ReminderLog::create([
            'appointment_id' => $appointment->id,
            'rule_id' => $rule->id,
            'channel' => 'email',
            'recipient' => $customer->email,
            'status' => 'pending',
            'scheduled_at' => now(),
        ]);

        $delivery = NotificationDelivery::create([
            'appointment_id' => $appointment->id,
            'public_reference' => $appointment->public_reference,
            'event' => 'appointment.reminder_1h',
            'channel' => 'email',
            'recipient' => $customer->email,
            'provider' => 'mail',
            'status' => 'queued',
            'attempts' => 0,
            'dedupe_key' => 'appointment.reminder_1h|email|' . $appointment->public_reference,
            'queued_at' => now(),
            'metadata' => [
                'rule_id' => $rule->id,
                'trigger_minutes' => 60,
                'reminder_log_id' => $log->id,
            ],
        ]);

        (new SendAppointmentReminderEmail(
            tenant: $this->tenant,
            deliveryId: (int) $delivery->id,
            data: [
                'appointment_id' => $appointment->id,
                'customer_id' => $customer->id,
                'customer_type' => 'customer',
                'reminder_log_id' => $log->id,
                'recipient' => $customer->email,
                'locale' => 'en',
            ],
        ))->handle();

        $delivery->refresh();
        $log->refresh();

        self::assertSame('sent', $delivery->status);
        self::assertSame(1, $delivery->attempts);
        self::assertNotNull($delivery->sent_at);
        self::assertSame('sent', $log->status);
        self::assertNotNull($log->sent_at);

        Mail::assertSent(\App\Mail\AppointmentReminderMail::class);
    }

    private function runReminderCommand(): void
    {
        Artisan::call('reminders:process', [
            '--tenant' => $this->tenant->id,
        ]);
    }

    private function makeRule(int $triggerMinutes): ReminderRule
    {
        return ReminderRule::create([
            'name' => [
                'en' => $triggerMinutes === 1440 ? '24 hour reminder' : '1 hour reminder',
                'ar' => $triggerMinutes === 1440 ? 'تذكير قبل 24 ساعة' : 'تذكير قبل ساعة',
            ],
            'trigger_type' => 'before_appointment',
            'trigger_minutes' => $triggerMinutes,
            'channel' => 'email',
            'template_key' => 'appointment_reminder',
            'template_vars' => [],
            'send_to_customer' => true,
            'send_to_staff' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function makeAppointment(Carbon $start): Appointment
    {
        $customer = Customer::create([
            'first_name' => 'Reminder',
            'last_name' => 'Customer',
            'email' => 'reminder-' . uniqid('', true) . '@example.com',
            'phone' => '+201000000099',
            'language' => 'en',
            'timezone' => config('app.timezone'),
            'is_blocked' => false,
        ]);

        return Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'customer_id_new' => $customer->id,
            'staff_id_new' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => $start->toDateString(),
            'time_slot' => $start->format('H:i'),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(30),
            'status' => Appointment::STATUS_CONFIRMED,
            'source' => 'public',
        ]);
    }
}
