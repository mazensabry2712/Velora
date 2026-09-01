<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Application\Booking\Actions\CreatePublicBooking;
use App\Application\Booking\DTOs\PublicBookingData;
use App\Models\Appointment;
use App\Models\BusinessRule;
use App\Models\Invoice;
use App\Models\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class MasterBusinessFlowScenarioTest extends TenantTestCase
{
    private function prepareBookableSlot(): array
    {
        $timezone = $this->staff->timezone ?: config('app.timezone');

        $this->service->forceFill([
            'is_active' => true,
            'is_online_bookable' => true,
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
        ])->save();

        $date = now($timezone)->addDay()->startOfDay();

        \App\Models\StaffWorkingHours::create([
            'staff_id' => $this->staff->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_working' => true,
        ]);

        RateLimiter::clear('public-booking:' . $this->tenant->getTenantKey() . ':' . $this->app->make('request')->ip());

        return [$date, $timezone];
    }

    #[Test]
    public function business_rule_schema_matches_the_contract_used_by_the_booking_engine(): void
    {
        foreach (['key', 'value', 'type', 'description', 'is_active'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('business_rules', $column),
                "business_rules.{$column} must exist for BusinessRule::getValue()."
            );
        }

        BusinessRule::setValue(
            BusinessRule::MIN_ADVANCE_BOOKING_HOURS,
            2,
            'integer',
        );

        $this->assertSame(2, BusinessRule::getValue(BusinessRule::MIN_ADVANCE_BOOKING_HOURS));
    }

    #[Test]
    public function application_booking_use_case_creates_a_consistent_booking_without_http_infrastructure(): void
    {
        [$date, $timezone] = $this->prepareBookableSlot();

        $result = app(CreatePublicBooking::class)->execute(new PublicBookingData(
            customerName: 'QA Application Customer',
            customerEmail: 'qa-application@example.com',
            customerPhone: '+201000000002',
            serviceId: $this->service->id,
            staffUserId: $this->staffMember->id,
            resourceId: null,
            appointmentDate: $date->toDateString(),
            appointmentTime: '09:00',
            requestedTimezone: $timezone,
            notes: 'Application layer scenario',
        ));

        $appointment = $result['appointment'];
        $queue = $result['queue'];
        $customer = $result['customer'];

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertInstanceOf(Queue::class, $queue);
        $this->assertSame($this->service->id, $appointment->service_id);
        $this->assertSame($this->staff->id, $appointment->staff_id_new);
        $this->assertSame($customer->id, $appointment->customer_id_new);
        $this->assertSame($appointment->id, $queue->appointment_id);
        $this->assertSame('pending', $appointment->status);
        $this->assertSame('waiting', $queue->status);
        $this->assertSame($date->toDateString(), $queue->queue_date->toDateString());
        $this->assertNotSame('', (string) $appointment->public_reference);
    }

    #[Test]
    public function golden_booking_flow_keeps_appointment_queue_customer_and_notification_state_consistent(): void
    {
        [$date, $timezone] = $this->prepareBookableSlot();

        $response = $this->postJson('/api/appointments', [
            'customer_name' => 'QA Golden Customer',
            'customer_email' => 'qa-golden@example.com',
            'customer_phone' => '+201000000001',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => $timezone,
            'notes' => 'Golden scenario',
        ]);

        if ($response->status() !== 201) {
            fwrite(STDERR, "Public booking response: {$response->getContent()}\n");
        }

        $response->assertCreated()->assertJsonPath('success', true);

        $appointment = Appointment::query()->latest('id')->firstOrFail();
        $queue = Queue::query()->where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertSame($this->tenant->id, tenant()->id);
        $this->assertSame($this->service->id, $appointment->service_id);
        $this->assertSame($this->staff->id, $appointment->staff_id_new);
        $this->assertSame('pending', $appointment->status);
        $this->assertSame('waiting', $queue->status);
        $this->assertSame($date->toDateString(), $queue->queue_date->toDateString());
        $this->assertNotSame('', (string) $appointment->public_reference);
        $this->assertNotNull($appointment->customer_id_new);

        $this->assertDatabaseHas('appointment_status_histories', [
            'appointment_id' => $appointment->id,
            'to_status' => Appointment::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('notification_deliveries', [
            'appointment_id' => $appointment->id,
            'event' => 'appointment.booked',
            'channel' => 'email',
            'public_reference' => $appointment->public_reference,
        ]);

        $this->getJson('/api/queue/status/' . rawurlencode((string) $queue->queue_number))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.queue_number', $queue->queue_number)
            ->assertJsonPath('data.status', 'waiting');

        $availability = $this->getJson('/api/booking/available-timeslots?' . http_build_query([
            'date' => $date->toDateString(),
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => $timezone,
        ]));

        $availability->assertOk()->assertJsonPath('success', true);
        $this->assertNotContains('09:00', collect($availability->json('data'))->pluck('start_time')->all());
    }

    #[Test]
    public function dashboard_reconciles_exactly_with_database_truth_for_the_golden_dataset(): void
    {
        $this->actingAs($this->admin);

        $today = today()->toDateString();

        $pending = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => $today,
            'time_slot' => '09:00',
            'status' => Appointment::STATUS_PENDING,
            'price' => 100,
        ]);

        $confirmed = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => $today,
            'time_slot' => '10:00',
            'status' => Appointment::STATUS_CONFIRMED,
            'price' => 100,
        ]);

        $completed = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => $today,
            'time_slot' => '11:00',
            'status' => Appointment::STATUS_PENDING,
            'price' => 100,
        ]);
        $completed->update(['status' => Appointment::STATUS_COMPLETED]);

        Queue::create([
            'appointment_id' => $pending->id,
            'queue_number' => 'A901',
            'queue_date' => $today,
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        Queue::create([
            'appointment_id' => $confirmed->id,
            'queue_number' => 'A902',
            'queue_date' => $today,
            'status' => 'serving',
            'is_vip' => true,
        ]);

        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();

        $stats = $response->viewData('stats');
        $statusDistribution = $response->viewData('statusDistribution');
        $todayAppointments = $response->viewData('todayAppointments');
        $currentQueue = $response->viewData('currentQueue');
        $topServices = $response->viewData('topServices');

        $this->assertSame(Appointment::count(), $stats['total_appointments']);
        $this->assertSame(Appointment::whereDate('date', today())->where('status', 'confirmed')->count(), $stats['confirmed']);
        $this->assertSame(Queue::whereIn('status', ['waiting', 'serving'])->count(), $stats['queue']);
        $this->assertSame(Appointment::whereDate('date', today())->count(), $todayAppointments->count());
        $this->assertSame(Queue::whereIn('status', ['waiting', 'serving'])->count(), $currentQueue->count());
        $this->assertSame(Appointment::where('status', 'pending')->count(), $statusDistribution['pending']);
        $this->assertSame(Appointment::where('status', 'confirmed')->count(), $statusDistribution['confirmed']);
        $this->assertSame(Appointment::where('status', 'completed')->count(), $statusDistribution['completed']);

        $top = $topServices->first();
        $this->assertNotNull($top);
        $this->assertSame($this->service->name, $top->name);
        $this->assertSame(
            Appointment::where('service_id', $this->service->id)->count(),
            (int) $top->total,
        );

        $this->assertSame(1, Invoice::where('appointment_id', $completed->id)->count());
    }

    #[Test]
    public function appointment_and_queue_state_transitions_remain_consistent(): void
    {
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'date' => today()->toDateString(),
            'time_slot' => '14:00',
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 'A950',
            'queue_date' => today()->toDateString(),
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $queue->update(['status' => 'serving']);
        $this->assertSame('serving', $queue->fresh()->status);
        $this->assertSame(Appointment::STATUS_CONFIRMED, $appointment->fresh()->status);

        $appointment->update(['status' => Appointment::STATUS_COMPLETED]);

        $this->assertSame(Appointment::STATUS_COMPLETED, $appointment->fresh()->status);
        $this->assertSame('completed', $queue->fresh()->status);

        $this->assertDatabaseHas('invoices', [
            'appointment_id' => $appointment->id,
            'customer_id' => $this->customer->id,
            'status' => 'pending',
        ]);
    }
}
