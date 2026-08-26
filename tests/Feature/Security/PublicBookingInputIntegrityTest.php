<?php

namespace Tests\Feature\Security;

use App\Models\Resource;
use App\Models\Service;
use App\Models\Staff;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

class PublicBookingInputIntegrityTest extends TenantTestCase
{
    #[Test]
    public function public_booking_rejects_non_bookable_staff(): void
    {
        $service = Service::create([
            'name' => 'Online Service',
            'duration' => 30,
            'price' => 50,
            'is_active' => true,
            'is_online_bookable' => true,
        ]);

        $staff = Staff::where('user_id', $this->staffMember->id)->firstOrFail();
        $staff->update(['is_active' => false]);

        $response = $this->postJson('/api/appointments', [
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+201000000000',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00',
            'service_id' => $service->id,
            'staff_id' => $staff->user_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['staff_id']);
    }

    #[Test]
    public function public_booking_rejects_non_bookable_service(): void
    {
        $staff = Staff::where('user_id', $this->staffMember->id)->firstOrFail();

        $service = Service::create([
            'name' => 'Private Service',
            'duration' => 30,
            'price' => 50,
            'is_active' => true,
            'is_online_bookable' => false,
        ]);

        $response = $this->postJson('/api/appointments', [
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+201000000000',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00',
            'service_id' => $service->id,
            'staff_id' => $staff->user_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['service_id']);
    }

    #[Test]
    public function public_booking_rejects_staff_not_assigned_to_service(): void
    {
        $service = Service::create([
            'name' => 'Unassigned Service',
            'duration' => 30,
            'price' => 50,
            'is_active' => true,
            'is_online_bookable' => true,
        ]);

        $staff = Staff::where('user_id', $this->staffMember->id)->firstOrFail();

        $response = $this->postJson('/api/appointments', [
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+201000000000',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00',
            'service_id' => $service->id,
            'staff_id' => $staff->user_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['staff_id']);
    }

    #[Test]
    public function public_booking_rejects_resource_not_assigned_to_service(): void
    {
        $service = Service::create([
            'name' => 'Resource Service',
            'duration' => 30,
            'price' => 50,
            'is_active' => true,
            'is_online_bookable' => true,
        ]);

        $staff = Staff::where('user_id', $this->staffMember->id)->firstOrFail();
        $staff->services()->attach($service->id);

        $resource = Resource::create([
            'name' => ['en' => 'Unassigned Room'],
            'type' => 'room',
            'quantity' => 1,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/appointments', [
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+201000000000',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00',
            'service_id' => $service->id,
            'staff_id' => $staff->user_id,
            'resource_id' => $resource->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resource_id']);
    }
}
