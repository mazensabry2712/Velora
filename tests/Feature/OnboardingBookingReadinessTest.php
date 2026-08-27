<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffWorkingHours;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

class OnboardingBookingReadinessTest extends TenantTestCase
{
    #[Test]
    public function new_tenant_can_finish_onboarding_and_immediately_receive_booking_slots(): void
    {
        DB::table('staff_services')->delete();
        StaffWorkingHours::query()->delete();
        Staff::query()->delete();
        Service::query()->delete();

        $this->actingAs($this->admin);

        $this->postJson(route('admin.onboarding.step1'), [
            'business_name' => 'My New Velora Clinic',
            'phone' => '+201000000000',
            'address' => 'Cairo',
        ])->assertOk()->assertJson([
            'success' => true,
            'next_step' => 2,
        ]);

        $this->postJson(route('admin.onboarding.step2'), [
            'name' => 'Ahmed Staff',
            'specialty' => 'Consultation',
        ])->assertOk()->assertJson([
            'success' => true,
            'next_step' => 3,
        ]);

        $staff = Staff::query()->firstOrFail();

        $this->assertTrue($staff->is_active);
        $this->assertTrue($staff->accepts_bookings);
        $this->assertCount(7, StaffWorkingHours::query()->where('staff_id', $staff->id)->where('is_working', true)->get());

        $this->postJson(route('admin.onboarding.step3'), [
            'name' => 'Consultation',
            'duration' => 30,
            'price' => 100,
        ])->assertOk()->assertJson([
            'success' => true,
            'next_step' => 4,
        ]);

        $service = Service::query()->firstOrFail();

        $this->assertDatabaseHas('staff_services', [
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'user_id' => $this->admin->id,
        ]);

        $this->postJson(route('admin.onboarding.complete'))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertTrue((bool) $this->tenant->run(
            fn () => \App\Models\Setting::query()->firstOrFail()->onboarding_completed
        ));

        $date = now()->addDay()->toDateString();

        $availability = $this->getJson('/api/booking/available-timeslots?' . http_build_query([
            'date' => $date,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'timezone' => config('app.timezone'),
        ]));

        $availability->assertOk()->assertJsonPath('success', true);
        $this->assertNotEmpty($availability->json('data'));
        $this->assertSame('09:00', $availability->json('data.0.start_time'));
    }
}
