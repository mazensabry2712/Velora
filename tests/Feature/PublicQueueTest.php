<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\Queue;
use App\Models\Setting;
use App\Models\User;
use App\Models\Role;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublicQueueTest extends TestCase
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
    public function queue_dashboard_page_loads_successfully()
    {
        $response = $this->get('/queue');

        $response->assertStatus(200);
        $response->assertViewIs('queue.dashboard');
    }

    /** @test */
    public function queue_dashboard_displays_business_name()
    {
        Setting::create([
            'tenant_id' => $this->tenant->id,
            'business_name' => 'Queue Test Business',
        ]);

        $response = $this->get('/queue');

        $response->assertStatus(200);
        $response->assertSee('Queue Test Business');
    }

    /** @test */
    public function public_queue_api_returns_correct_structure()
    {
        $response = $this->getJson('/api/queue');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current',
                    'queues',
                    'total_waiting',
                ]
            ]);
    }

    /** @test */
    public function public_queue_api_shows_current_serving()
    {
        // Create a customer and appointment first
        $customerRole = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'web']);
        $customer = User::create([
            'name' => 'Test Customer',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'time_slot' => '10:00',
            'status' => 'confirmed',
        ]);

        // Create a queue that is being served
        Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 1,
            'status' => 'serving',
            'is_vip' => false,
        ]);

        $response = $this->getJson('/api/queue');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'current' => [
                        'queue_number' => 1,
                    ],
                ]
            ]);
    }

    /** @test */
    public function public_queue_api_shows_waiting_queues()
    {
        // Create customer and appointments
        $customerRole = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'web']);
        $customer = User::create([
            'name' => 'Test Customer',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
        ]);

        $appointment1 = Appointment::create([
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'time_slot' => '10:00',
            'status' => 'pending',
        ]);

        $appointment2 = Appointment::create([
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'time_slot' => '10:30',
            'status' => 'pending',
        ]);

        // Create waiting queues
        Queue::create([
            'appointment_id' => $appointment1->id,
            'queue_number' => 2,
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        Queue::create([
            'appointment_id' => $appointment2->id,
            'queue_number' => 3,
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $response = $this->getJson('/api/queue');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_waiting' => 2,
                ]
            ]);
    }

    /** @test */
    public function public_queue_api_does_not_expose_customer_names()
    {
        // Create customer
        $customerRole = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'web']);
        $customer = User::create([
            'name' => 'Secret Customer Name',
            'email' => 'customer@test.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
        ]);

        // Create appointment
        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'time_slot' => '10:00',
            'status' => 'confirmed',
        ]);

        // Create queue with appointment
        Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 5,
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $response = $this->getJson('/api/queue');

        $response->assertStatus(200)
            ->assertDontSee('Secret Customer Name');
    }

    /** @test */
    public function public_queue_api_only_returns_queue_numbers_for_waiting()
    {
        // Create customer and appointment
        $customerRole = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'web']);
        $customer = User::create([
            'name' => 'Test Customer',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'time_slot' => '10:00',
            'status' => 'pending',
        ]);

        Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 10,
            'status' => 'waiting',
            'is_vip' => true, // VIP should not be exposed
        ]);

        $response = $this->getJson('/api/queue');

        $response->assertStatus(200);

        $data = $response->json('data.queues');

        // Each queue item should only have queue_number and status
        foreach ($data as $queue) {
            $this->assertArrayHasKey('queue_number', $queue);
            $this->assertArrayHasKey('status', $queue);
            $this->assertArrayNotHasKey('is_vip', $queue);
            $this->assertArrayNotHasKey('customer_name', $queue);
        }
    }

    /** @test */
    public function public_queue_api_filters_by_today()
    {
        // Create customer and appointments
        $customerRole = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'web']);
        $customer = User::create([
            'name' => 'Test Customer',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
        ]);

        $appointment1 = Appointment::create([
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'time_slot' => '10:00',
            'status' => 'pending',
        ]);

        $appointment2 = Appointment::create([
            'customer_id' => $customer->id,
            'date' => now()->subDay()->toDateString(),
            'time_slot' => '10:00',
            'status' => 'pending',
        ]);

        // Create queue for today
        Queue::create([
            'appointment_id' => $appointment1->id,
            'queue_number' => 1,
            'status' => 'waiting',
            'is_vip' => false,
            'created_at' => now(),
        ]);

        // Create queue for yesterday (should not appear)
        Queue::create([
            'appointment_id' => $appointment2->id,
            'queue_number' => 100,
            'status' => 'waiting',
            'is_vip' => false,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/queue');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_waiting' => 1,
                ]
            ]);
    }

    /** @test */
    public function public_queue_api_returns_null_when_no_one_serving()
    {
        // Create customer and appointment
        $customerRole = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'web']);
        $customer = User::create([
            'name' => 'Test Customer',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'role_id' => $customerRole->id,
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'time_slot' => '10:00',
            'status' => 'pending',
        ]);

        // Only create waiting queue, no serving
        Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => 1,
            'status' => 'waiting',
            'is_vip' => false,
        ]);

        $response = $this->getJson('/api/queue');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'current' => null,
                ]
            ]);
    }

    /** @test */
    public function queue_page_has_link_to_booking()
    {
        $response = $this->get('/queue');

        $response->assertStatus(200);
        $response->assertSee('/book');
    }

    /** @test */
    public function booking_page_has_link_to_queue()
    {
        $response = $this->get('/book');

        $response->assertStatus(200);
        $response->assertSee('/queue');
    }
}
