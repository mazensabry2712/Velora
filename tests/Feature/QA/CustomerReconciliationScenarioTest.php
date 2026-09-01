<?php

declare(strict_types=1);

namespace Tests\Feature\QA;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\StaffWorkingHours;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('qa')]
#[Group('master-scenario')]
final class CustomerReconciliationScenarioTest extends TenantTestCase
{
    #[Test]
    public function booking_customer_is_counted_by_customer_api_and_tenant_dashboard(): void
    {
        $timezone = $this->staff->timezone ?: config('app.timezone');
        $date = now($timezone)->addDay()->startOfDay();

        $this->service->forceFill([
            'is_active' => true,
            'is_online_bookable' => true,
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
        ])->save();

        StaffWorkingHours::create([
            'staff_id' => $this->staff->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_working' => true,
        ]);

        RateLimiter::clear('public-booking:' . $this->tenant->getTenantKey() . ':' . $this->app->make('request')->ip());

        $response = $this->postJson('/api/appointments', [
            'customer_name' => 'QA Customer Reconciliation',
            'customer_email' => 'qa-customer-reconciliation@example.com',
            'customer_phone' => '+201000000004',
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => $timezone,
        ]);

        $response->assertCreated()->assertJsonPath('success', true);

        $appointment = Appointment::query()->latest('id')->firstOrFail();
        $customer = Customer::query()->where('email', 'qa-customer-reconciliation@example.com')->firstOrFail();

        $this->assertSame($customer->id, $appointment->customer_id_new);
        $this->assertSame(1, $customer->appointments()->count());

        $this->actingAs($this->admin);

        $api = $this->getJson(route('admin.api.customers.show', ['id' => $customer->id]));
        $api->assertOk()
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('stats.total_appointments', 1);

        $dashboard = $this->get(route('admin.dashboard'));
        $dashboard->assertOk();
        $stats = $dashboard->viewData('stats');

        $this->assertSame(Customer::count(), $stats['customers']);
        $this->assertSame(Appointment::count(), $stats['total_appointments']);
    }

    #[Test]
    public function blocked_customer_cannot_create_a_public_booking(): void
    {
        $blocked = Customer::create([
            'first_name' => 'Blocked',
            'last_name' => 'QA Customer',
            'email' => 'qa-blocked-customer@example.com',
            'phone' => '+201000000005',
            'is_blocked' => true,
            'block_reason' => 'QA blocked customer',
        ]);

        $timezone = $this->staff->timezone ?: config('app.timezone');
        $date = now($timezone)->addDay()->startOfDay();
        $before = Appointment::count();

        $this->service->forceFill([
            'is_active' => true,
            'is_online_bookable' => true,
        ])->save();

        StaffWorkingHours::create([
            'staff_id' => $this->staff->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'is_working' => true,
        ]);

        RateLimiter::clear('public-booking:' . $this->tenant->getTenantKey() . ':' . $this->app->make('request')->ip());

        $response = $this->postJson('/api/appointments', [
            'customer_name' => $blocked->full_name,
            'customer_email' => $blocked->email,
            'customer_phone' => $blocked->phone,
            'appointment_date' => $date->toDateString(),
            'appointment_time' => '09:00',
            'staff_id' => $this->staffMember->id,
            'service_id' => $this->service->id,
            'timezone' => $timezone,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['customer_email']);
        $this->assertSame($before, Appointment::count());
    }
}
