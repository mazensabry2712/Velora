<?php

namespace Tests\Feature\Requests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

#[Group('feature')]
#[Group('requests')]
#[Group('staff')]
class StaffRequestTest extends TenantTestCase
{
    // ════════════════════════════════════════════════════════════════════════
    // StoreStaffRequest
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function store_passes_with_valid_data(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.staff.store'), [
            'name'           => 'Dr. Valid',
            'email'          => 'drvalid@clinic.com',
            'specialization' => 'General',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function store_requires_name(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.staff.store'), [
            'email'          => 'drnoname@clinic.com',
            'specialization' => 'General',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function store_requires_email(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.staff.store'), [
            'name'           => 'Dr. No Email',
            'specialization' => 'General',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function store_requires_specialization(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.staff.store'), [
            'name'  => 'Dr. No Spec',
            'email' => 'nospce@clinic.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['specialization']);
    }

    #[Test]
    public function store_rejects_invalid_email_format(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.staff.store'), [
            'name'           => 'Dr. Bad Email',
            'email'          => 'not-valid-email',
            'specialization' => 'General',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function store_rejects_email_already_used_by_another_user(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.staff.store'), [
            'name'           => 'Duplicate',
            'email'          => $this->staffMember->email, // already exists
            'specialization' => 'General',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function store_accepts_optional_schedule_array(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.staff.store'), [
            'name'           => 'Dr. With Schedule',
            'email'          => 'drschedule@clinic.com',
            'specialization' => 'ENT',
            'schedule'       => [
                ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '17:00', 'is_active' => true],
                ['day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '17:00', 'is_active' => true],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function store_rejects_schedule_with_end_time_before_start_time(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.staff.store'), [
            'name'           => 'Dr. Bad Schedule',
            'email'          => 'drbadsched@clinic.com',
            'specialization' => 'General',
            'schedule'       => [
                ['day_of_week' => 0, 'start_time' => '17:00', 'end_time' => '08:00'], // reversed
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['schedule.0.end_time']);
    }

    #[Test]
    public function store_rejects_service_id_that_does_not_exist(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.staff.store'), [
            'name'           => 'Dr. Bad Service',
            'email'          => 'drnoservice@clinic.com',
            'specialization' => 'General',
            'services'       => [99999], // non-existent service
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['services.0']);
    }

    #[Test]
    public function store_accepts_valid_services_array(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.api.staff.store'), [
            'name'           => 'Dr. With Service',
            'email'          => 'drwithservice@clinic.com',
            'specialization' => 'General',
            'services'       => [$this->service->id],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    // ════════════════════════════════════════════════════════════════════════
    // UpdateStaffRequest
    // ════════════════════════════════════════════════════════════════════════

    #[Test]
    public function update_allows_same_email_for_current_user(): void
    {
        $this->actingAs($this->admin);

        $response = $this->putJson(route('admin.api.staff.update', $this->staffMember->id), [
            'name'           => 'Same Email Update',
            'email'          => $this->staffMember->email, // same as existing
            'specialization' => 'Updated Spec',
        ]);

        // Should not throw email unique violation
        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function update_rejects_email_taken_by_another_user(): void
    {
        $this->actingAs($this->admin);

        $response = $this->putJson(route('admin.api.staff.update', $this->staffMember->id), [
            'name'           => 'Take Admin Email',
            'email'          => $this->admin->email, // taken by admin
            'specialization' => 'Spec',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}

