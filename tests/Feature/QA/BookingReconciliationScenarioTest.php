<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\Appointment;
use App\Models\NotificationDelivery;
use App\Models\Queue;
use App\Models\StaffWorkingHours;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class BookingReconciliationScenarioTest extends TenantTestCase
{
    #[Test]
    public function real_booking_reconciles_across_database_queue_notifications_and_tenant_dashboard(): void
    {
        $timezone = $this->staff->timezone ?: config('app.timezone');
        $beforeAppointments = Appointment::count();
        $beforeQueues = Queue::whereIn('status', ['waiting', 'serving'])->count();
        $beforeNotifications = NotificationDelivery::count();

        $this->service->forceFill([
            'is_active' => true,
            'is_online_bookable' => true,
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
        ])->save();

        $date = now($timezone)->addDay()->startOfDay();
        StaffWorkingHours::create([
            'staff_id' => $this->staff->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_working' => true,
        ]);

        RateLimiter::clear('public-booking:' . $this->tenant->getTenantKey() . ':' . $this->app->make('request')->ip());

        $response = $this->postJson('/api/appointments', [
            'customer_name' => 'QA Reconciliation Customer',
            'customer_email' => 'qa-reconciliation@example.com',
            'customer_phone' => '+201000000003',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => $timezone,
            'notes' => 'Cross-surface reconciliation',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);

        $appointment = Appointment::query()->latest('id')->firstOrFail();
        $queue = Queue::query()->where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertSame($beforeAppointments + 1, Appointment::count());
        $this->assertSame($appointment->id, $queue->appointment_id);
        $this->assertSame($this->service->id, $appointment->service_id);
        $this->assertSame($this->staff->id, $appointment->staff_id_new);
        $this->assertSame('pending', $appointment->status);
        $this->assertSame('waiting', $queue->status);
        $this->assertSame($beforeQueues + 1, Queue::whereIn('status', ['waiting', 'serving'])->count());

        $this->assertSame($beforeNotifications + 1, NotificationDelivery::count());
        $delivery = NotificationDelivery::query()
            ->where('appointment_id', $appointment->id)
            ->where('event', 'appointment.booked')
            ->where('channel', 'email')
            ->where('public_reference', $appointment->public_reference)
            ->first();

        $this->assertNotNull($delivery);
        $this->assertContains($delivery->status, ['queued', 'sending', 'sent']);
        $this->assertNotNull($delivery->queued_at);

        $this->actingAs($this->admin);
        $dashboard = $this->get(route('admin.dashboard'));
        $dashboard->assertOk();

        $stats = $dashboard->viewData('stats');
        $currentQueue = $dashboard->viewData('currentQueue');
        $topServices = $dashboard->viewData('topServices');

        $this->assertSame(Appointment::count(), $stats['total_appointments']);
        $this->assertSame(Queue::whereIn('status', ['waiting', 'serving'])->count(), $stats['queue']);
        $this->assertTrue($currentQueue->contains('id', $queue->id));

        $top = $topServices->firstWhere('id', $this->service->id);
        $this->assertNotNull($top);
        $this->assertSame(
            Appointment::where('service_id', $this->service->id)->count(),
            (int) $top->total,
        );
    }
}
