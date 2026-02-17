<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\Service;
use App\Models\User;
use App\Models\Role;
use App\Models\Queue;
use App\Models\TimeSlot;
use App\Models\WorkingDay;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test tenant
        $this->tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Business',
        ]);

        // Create domain for tenant
        $this->tenant->domains()->create(['domain' => 'test.localhost']);

        // Initialize tenant context
        tenancy()->initialize($this->tenant);

        // Run tenant migrations
        $this->artisan('tenants:migrate', ['--tenants' => [$this->tenant->id]]);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        parent::tearDown();
    }

    /** @test */
    public function booking_page_loads_successfully()
    {
        $response = $this->get('/book');

        $response->assertStatus(200);
        $response->assertViewIs('customer.booking');
    }

    /** @test */
    public function booking_page_displays_business_name()
    {
        // Create settings with business name
        Setting::create([
            'tenant_id' => $this->tenant->id,
            'business_name' => 'My Test Business',
        ]);

        $response = $this->get('/book');

        $response->assertStatus(200);
        $response->assertSee('My Test Business');
    }

    /** @test */
    public function services_api_returns_active_services()
    {
        // Create services
        Service::create([
            'name' => 'Haircut',
            'name_ar' => 'قص شعر',
            'duration' => 30,
            'price' => 50,
            'is_active' => true,
        ]);

        Service::create([
            'name' => 'Inactive Service',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/booking/services');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Haircut']);
    }

    /** @test */
    public function staff_by_service_api_returns_correct_staff()
    {
        // Create staff role
        $staffRole = Role::create(['name' => 'Staff', 'guard_name' => 'web']);

        // Create service
        $service = Service::create([
            'name' => 'Massage',
            'is_active' => true,
        ]);

        // Create staff member
        $staff = User::create([
            'name' => 'John Staff',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'role_id' => $staffRole->id,
        ]);

        // Attach service to staff
        $staff->services()->attach($service->id);

        $response = $this->getJson('/api/booking/staff/by-service/' . $service->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['name' => 'John Staff']);
    }

    /** @test */
    public function timeslots_api_returns_active_slots()
    {
        TimeSlot::create([
            'start_time' => '09:00',
            'end_time' => '10:00',
            'is_active' => true,
        ]);

        TimeSlot::create([
            'start_time' => '10:00',
            'end_time' => '11:00',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/booking/timeslots');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function workingdays_api_returns_active_days()
    {
        WorkingDay::create([
            'day_of_week' => 1, // Monday
            'is_active' => true,
        ]);

        WorkingDay::create([
            'day_of_week' => 6, // Saturday
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/booking/workingdays');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function language_can_be_changed()
    {
        $response = $this->get('/change-language/ar');

        $response->assertRedirect();
        $this->assertEquals('ar', session('locale'));
    }

    /** @test */
    public function invalid_language_is_rejected()
    {
        session(['locale' => 'en']);

        $response = $this->get('/change-language/invalid');

        $response->assertRedirect();
        // Session should remain unchanged
        $this->assertEquals('en', session('locale'));
    }
}
