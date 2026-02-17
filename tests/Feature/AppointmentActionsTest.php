<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Appointment;
use App\Models\Queue;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

class AppointmentActionsTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $admin;
    protected $customer;
    protected $staff;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Run central migrations
        Artisan::call('migrate', ['--database' => config('tenancy.database.central_connection'), '--path' => 'database/migrations']);

        // Create tenant
        $this->tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Clinic',
        ]);

        // Initialize tenant and run tenant migrations
        tenancy()->initialize($this->tenant);
        Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

        // Create roles
        $adminRole = Role::create(['name' => 'Admin Tenant']);
        $customerRole = Role::create(['name' => 'Customer']);
        $staffRole = Role::create(['name' => 'Staff']);

        // Create users
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
        ]);

        $this->customer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '1234567890',
            'password' => Hash::make('password'),
            'role_id' => $customerRole->id,
        ]);

        $this->staff = User::create([
            'name' => 'Staff Member',
            'email' => 'staff@test.com',
            'password' => Hash::make('password'),
            'role_id' => $staffRole->id,
        ]);

        // Create service
        $this->service = Service::create([
            'name' => 'Consultation',
            'name_ar' => 'استشارة',
            'duration' => 30,
            'price' => 100,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_displays_appointments_page()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.appointments'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.appointments.index');
        $response->assertViewHas('appointments');
        $response->assertViewHas('stats');
    }

    /** @test */
    public function it_creates_appointment_and_adds_to_queue_automatically()
    {
        $this->actingAs($this->admin);

        $appointmentData = [
            'customer_name' => 'New Customer',
            'customer_email' => 'newcustomer@test.com',
            'customer_phone' => '555-1234',
            'appointment_date' => now()->addDays(1)->format('Y-m-d H:i'),
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
            'notes' => 'Test appointment',
        ];

        $response = $this->postJson(route('admin.api.appointments.store'), $appointmentData);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);

        // Check appointment was created
        $this->assertDatabaseHas('appointments', [
            'service_id' => $this->service->id,
            'staff_id' => $this->staff->id,
        ]);

        // Check queue was created automatically
        $appointment = Appointment::latest()->first();
        $this->assertNotNull($appointment->queue);
        $this->assertEquals('waiting', $appointment->queue->status);
    }

    /** @test */
    public function it_updates_appointment_status()
    {
        $this->actingAs($this->admin);

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now()->addDays(1),
            'time_slot' => '10:00',
            'status' => 'pending',
        ]);

        $response = $this->patchJson(route('admin.api.appointments.status', $appointment->id), [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $appointment->refresh();
        $this->assertEquals('confirmed', $appointment->status);
    }

    /** @test */
    public function it_syncs_queue_status_when_appointment_is_cancelled()
    {
        $this->actingAs($this->admin);

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now(),
            'time_slot' => '10:00',
            'status' => 'confirmed',
        ]);

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => '1',
            'status' => 'waiting',
        ]);

        // Cancel appointment
        $response = $this->patchJson(route('admin.api.appointments.status', $appointment->id), [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(200);

        // Check queue status was updated to skipped
        $queue->refresh();
        $this->assertEquals('skipped', $queue->status);
    }

    /** @test */
    public function it_syncs_queue_status_when_appointment_is_completed()
    {
        $this->actingAs($this->admin);

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now(),
            'time_slot' => '10:00',
            'status' => 'confirmed',
        ]);

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => '1',
            'status' => 'serving',
        ]);

        // Complete appointment
        $response = $this->patchJson(route('admin.api.appointments.status', $appointment->id), [
            'status' => 'completed',
        ]);

        $response->assertStatus(200);

        // Check queue status was updated to completed
        $queue->refresh();
        $this->assertEquals('completed', $queue->status);
    }

    /** @test */
    public function it_adds_appointment_to_queue()
    {
        $this->actingAs($this->admin);

        // Create appointment without queue
        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now(),
            'time_slot' => '10:00',
            'status' => 'confirmed',
        ]);

        $this->assertNull($appointment->queue);

        // Add to queue
        $response = $this->postJson(route('admin.api.appointments.addToQueue', $appointment->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Check queue was created
        $appointment->refresh();
        $this->assertNotNull($appointment->queue);
        $this->assertEquals('waiting', $appointment->queue->status);
    }

    /** @test */
    public function it_prevents_adding_appointment_to_queue_twice()
    {
        $this->actingAs($this->admin);

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now(),
            'time_slot' => '10:00',
            'status' => 'confirmed',
        ]);

        Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => '1',
            'status' => 'waiting',
        ]);

        // Try to add again
        $response = $this->postJson(route('admin.api.appointments.addToQueue', $appointment->id));

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function it_removes_appointment_from_queue()
    {
        $this->actingAs($this->admin);

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now(),
            'time_slot' => '10:00',
            'status' => 'confirmed',
        ]);

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => '1',
            'status' => 'waiting',
        ]);

        // Remove from queue
        $response = $this->deleteJson(route('admin.api.appointments.removeFromQueue', $appointment->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Check queue was deleted
        $this->assertDatabaseMissing('queues', ['id' => $queue->id]);
    }

    /** @test */
    public function it_correctly_sets_vip_status_when_adding_to_queue()
    {
        $this->actingAs($this->admin);

        // Set customer as VIP
        $this->customer->update(['is_vip' => true]);

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now(),
            'time_slot' => '10:00',
            'status' => 'confirmed',
        ]);

        // Add to queue
        $response = $this->postJson(route('admin.api.appointments.addToQueue', $appointment->id));

        $response->assertStatus(200);

        // Check VIP status
        $appointment->refresh();
        $this->assertTrue($appointment->queue->is_vip);
    }

    /** @test */
    public function it_filters_appointments_by_queue_status()
    {
        $this->actingAs($this->admin);

        // Appointment in queue
        $appointmentInQueue = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now(),
            'time_slot' => '10:00',
            'status' => 'confirmed',
        ]);

        Queue::create([
            'appointment_id' => $appointmentInQueue->id,
            'queue_number' => '1',
            'status' => 'waiting',
        ]);

        // Appointment not in queue
        $appointmentNotInQueue = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now()->addDays(1),
            'time_slot' => '11:00',
            'status' => 'pending',
        ]);

        // Filter by in_queue
        $response = $this->get(route('admin.appointments', ['queue_status' => 'in_queue']));
        $response->assertStatus(200);

        // Filter by not_in_queue
        $response = $this->get(route('admin.appointments', ['queue_status' => 'not_in_queue']));
        $response->assertStatus(200);

        // Filter by waiting
        $response = $this->get(route('admin.appointments', ['queue_status' => 'waiting']));
        $response->assertStatus(200);
    }

    /** @test */
    public function it_deletes_appointment_and_cascades_queue()
    {
        $this->actingAs($this->admin);

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now(),
            'time_slot' => '10:00',
            'status' => 'confirmed',
        ]);

        $queue = Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => '1',
            'status' => 'waiting',
        ]);

        // Delete appointment
        $response = $this->deleteJson(route('admin.api.appointments.destroy', $appointment->id));

        $response->assertStatus(200);

        // Check both appointment and queue were deleted
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
        $this->assertDatabaseMissing('queues', ['id' => $queue->id]);
    }

    /** @test */
    public function it_displays_queue_number_in_appointments_list()
    {
        $this->actingAs($this->admin);

        $appointment = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now(),
            'time_slot' => '10:00',
            'status' => 'confirmed',
        ]);

        Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => '42',
            'status' => 'waiting',
        ]);

        $response = $this->get(route('admin.appointments'));

        $response->assertStatus(200);
        $response->assertSee('#42');
        $response->assertSee('في الانتظار');
    }

    /** @test */
    public function it_shows_correct_action_buttons_based_on_queue_status()
    {
        $this->actingAs($this->admin);

        // Appointment in queue
        $appointmentInQueue = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now(),
            'time_slot' => '10:00',
            'status' => 'confirmed',
        ]);

        Queue::create([
            'appointment_id' => $appointmentInQueue->id,
            'queue_number' => '1',
            'status' => 'waiting',
        ]);

        // Appointment not in queue
        $appointmentNotInQueue = Appointment::create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'date' => now()->addDays(1),
            'time_slot' => '11:00',
            'status' => 'pending',
        ]);

        $response = $this->get(route('admin.appointments'));

        $response->assertStatus(200);

        // Should see Remove button for appointment in queue
        $response->assertSee('removeFromQueue(' . $appointmentInQueue->id . ')');
        $response->assertSee('إزالة');

        // Should see Add button for appointment not in queue
        $response->assertSee('addToQueue(' . $appointmentNotInQueue->id . ')');
        $response->assertSee('إضافة');
    }
}
