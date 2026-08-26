<?php

namespace Tests\Feature;

use Tests\TenantTestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Setting;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\WorkingDay;
use Illuminate\Support\Facades\RateLimiter;

class PublicBookingTest extends TenantTestCase
{
    #[Test]
    public function booking_page_loads_successfully(): void
    {
        $response = $this->get('/book');
        $response->assertStatus(200);
        $response->assertViewIs('customer.booking');
    }

    #[Test]
    public function booking_page_displays_business_name(): void
    {
        Setting::create([
            'tenant_id'     => $this->tenant->id,
            'business_name' => 'My Test Business',
        ]);

        $response = $this->get('/book');
        $response->assertStatus(200);
        $response->assertViewIs('customer.booking');
    }

    #[Test]
    public function services_api_returns_active_services(): void
    {
        Service::create([
            'name'               => 'Haircut',
            'name_ar'            => 'قص شعر',
            'duration'           => 30,
            'price'              => 50,
            'is_active'          => true,
            'is_online_bookable' => true,
        ]);

        Service::create([
            'name'               => 'Inactive Service',
            'is_active'          => false,
            'is_online_bookable' => true,
        ]);

        $response = $this->getJson('/api/booking/services');
        $response->assertStatus(200)->assertJson(['success' => true]);

        $data = $response->json('data');
        $names = array_column($data, 'name');
        $this->assertContains('Haircut', $names);
        $this->assertNotContains('Inactive Service', $names);
    }

    #[Test]
    public function staff_by_service_api_returns_correct_staff(): void
    {
        $service = Service::create([
            'name' => 'Massage',
            'is_active' => true,
        ]);

        $this->staffMember->services()->attach($service->id);

        $response = $this->getJson('/api/booking/staff/by-service/' . $service->id);
        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonFragment(['name' => $this->staffMember->name]);
    }

    #[Test]
    public function timeslots_api_returns_active_slots(): void
    {
        TimeSlot::create(['start_time' => '09:00', 'end_time' => '10:00', 'is_active' => true]);
        TimeSlot::create(['start_time' => '10:00', 'end_time' => '11:00', 'is_active' => false]);

        $response = $this->getJson('/api/booking/timeslots');
        $response->assertStatus(200)->assertJson(['success' => true]);

        $activeSlots = collect($response->json('data'))->where('is_active', true);
        $this->assertCount(1, $activeSlots);
    }

    #[Test]
    public function workingdays_api_returns_active_days(): void
    {
        WorkingDay::query()->delete();
        WorkingDay::create(['day_of_week' => 1, 'day_name' => 'Monday', 'day_name_ar' => 'الاثنين', 'is_active' => true]);
        WorkingDay::create(['day_of_week' => 6, 'day_name' => 'Saturday', 'day_name_ar' => 'السبت', 'is_active' => false]);

        $response = $this->getJson('/api/booking/workingdays');
        $response->assertStatus(200)->assertJson(['success' => true]);

        $activeDays = collect($response->json('data'))->where('is_active', true);
        $this->assertCount(1, $activeDays);
    }

    #[Test]
    public function public_booking_is_rate_limited_per_tenant_and_ip(): void
    {
        $key = 'public-booking:' . $this->tenant->getTenantKey() . ':' . $this->app->make('request')->ip();
        RateLimiter::clear($key);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/appointments', [])->assertStatus(422);
        }

        $this->postJson('/api/appointments', [])
             ->assertStatus(429)
             ->assertJson([
                 'success' => false,
                 'message' => 'Too many booking attempts. Please try again later.',
             ]);

        $this->assertTrue(RateLimiter::tooManyAttempts($key, 5));
    }

    #[Test]
    public function language_can_be_changed(): void
    {
        $response = $this->get('/change-language/ar');
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'ar');
    }

    #[Test]
    public function invalid_language_is_rejected(): void
    {
        session(['locale' => 'en']);
        $response = $this->get('/change-language/invalid_lang_xyz');
        $response->assertRedirect();
        $this->assertNotEquals('invalid_lang_xyz', session('locale'));
    }

    #[Test]
    public function booking_page_has_link_to_queue(): void
    {
        $response = $this->get('/book');
        $response->assertStatus(200);
        $response->assertSee('/queue');
    }
}
